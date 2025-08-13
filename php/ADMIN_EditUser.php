<?php
// Admin page access
require_once 'auth_check.php';
requireRole(['Admin']); // only admins allowed
require_once 'db_connect.php';
require_once 'validation.php';
require_once 'security_logger.php';

$logger = new SecurityLogger($conn);
date_default_timezone_set('Asia/Manila');

$userID = $_GET['userID'] ?? null;
if (!$userID) {
  die("❌ No user ID provided.");
}

$error = '';
$user = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $firstName = $_POST['firstName'];
  $lastName  = $_POST['lastName'];
  $email     = $_POST['email'];
  $address   = $_POST['address'];
  $passwordPlain = $_POST['password'] ?? '';
  $role      = $_POST['role'];
  $joined    = $_POST['joined'];

  // Validation
  $errors = [];

  if (!validateString($firstName, 2, 50)) {
    $errors[] = "First name must be between 2 and 50 characters.";
    $logger->logInputValidationFailure("FirstName", "Length between 2 and 50", $firstName);
  }
  if (!validateString($lastName, 2, 50)) {
    $errors[] = "Last name must be between 2 and 50 characters.";
    $logger->logInputValidationFailure("LastName", "Length between 2 and 50", $lastName);
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
    $logger->logInputValidationFailure("Email", "Valid email format", $email);
  }
  if (!empty(trim($address)) && !validateString($address, 10, 200)) {
    $errors[] = "Address must be between 10 and 200 characters.";
    $logger->logInputValidationFailure("Address", "Length between 10 and 200", $address);
  }

  // Get current password info
  $dateStmt = $conn->prepare("SELECT Password, LastPasswordChange FROM USERS WHERE userID = ?");
  $dateStmt->bind_param('s', $userID);
  $dateStmt->execute();
  $currentData = $dateStmt->get_result()->fetch_assoc();
  $dateStmt->close();

  $updateFields = ["FirstName = ?", "LastName = ?", "Email = ?", "Address = ?", "Role = ?", "Created_At = ?"];
  $types = "ssssss";
  $params = [$firstName, $lastName, $email, $address, $role, $joined];

  // Only check password rules if admin entered a new password
  if (!empty($passwordPlain)) {
    $minLength = 8;
    if (
      strlen($passwordPlain) < $minLength ||
      !preg_match('/[A-Z]/', $passwordPlain) ||
      !preg_match('/[a-z]/', $passwordPlain) ||
      !preg_match('/[0-9]/', $passwordPlain) ||
      !preg_match('/[\W]/', $passwordPlain)
    ) {
      die("❌ Password must be at least $minLength characters long and include uppercase, lowercase, number, and special character.");
    }

    // Check if changed in the last 1 minute
    if (!empty($currentData['LastPasswordChange'])) {
      $lastChangeTime = strtotime($currentData['LastPasswordChange']);
      $nowTime = time();
      if (($nowTime - $lastChangeTime) < 60) {
        die("❌ You can change the password again in 1 minute.");
      }
    }

    // Prevent password reuse
    $reuseFound = false;
    $historyStmt = $conn->prepare("SELECT PasswordHash FROM USER_PASSWORD_HISTORY WHERE userID = ?");
    $historyStmt->bind_param('s', $userID);
    $historyStmt->execute();
    $historyResult = $historyStmt->get_result();
    while ($row = $historyResult->fetch_assoc()) {
      if (password_verify($passwordPlain, $row['PasswordHash'])) {
        $reuseFound = true;
        break;
      }
    }
    $historyStmt->close();

    if ($reuseFound || password_verify($passwordPlain, $currentData['Password'])) {
      die("❌ You cannot reuse any previous password.");
    }

    // Save current password to history
    $saveHistory = $conn->prepare("INSERT INTO USER_PASSWORD_HISTORY (userID, PasswordHash) VALUES (?, ?)");
    $saveHistory->bind_param('ss', $userID, $currentData['Password']);
    $saveHistory->execute();
    $saveHistory->close();

    // Add new password to update
    $updateFields[] = "Password = ?";
    $types .= "s";
    $params[] = password_hash($passwordPlain, PASSWORD_DEFAULT);

    $updateFields[] = "LastPasswordChange = NOW()";
  }

  if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: ADMIN_EditUser.php?userID=" . urlencode($userID));
    exit();
  }

  // Build query
  $types .= "s";
  $params[] = $userID;
  $sql = "UPDATE USERS SET " . implode(", ", $updateFields) . " WHERE userID = ?";
  $stmt = $conn->prepare($sql);

  if ($stmt) {
    $bindParams = array_merge([$types], $params);
    foreach ($bindParams as $key => $value) {
      $refs[$key] = &$bindParams[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);

    if ($stmt->execute()) {
      $logger->logEvent(
        'APPLICATION_SUCCESS',
        "User updated successfully: {$userID} ~ {$firstName} {$lastName}",
        $_SESSION['user_id'] ?? null,
        $_SESSION['role'] ?? null
      );
      header("Location: ADMIN_ManageUsers.php");
      exit();
    } else {
      $error = "Failed to update user.";
    }
    $stmt->close();
  }
}

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM USERS WHERE userID = ?");
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
  die("User not found.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Admin Edit User ‒ KALYE WEST</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html,
    body {
      height: 100%;
      font-family: Arial, sans-serif;
      background: #fff;
      color: #000;
    }

    body {
      display: flex;
      height: 100vh;
      overflow: hidden;
    }

    .sidebar {
      width: 220px;
      background: #111;
      color: white;
      padding: 40px 20px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .sidebar h4 {
      margin: 30px 0 10px;
      font-weight: bold;
      font-size: 14px;
    }

    .sidebar a {
      color: white;
      text-decoration: none;
      margin: 8px 0;
      display: block;
      font-size: 14px;
      transition: 0.2s;
    }

    .sidebar a.active {
      text-decoration: underline;
      text-underline-offset: 4px;
    }

    .sidebar a:hover {
      opacity: 0.7;
    }

    .sidebar .logout {
      margin-top: 40px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .main-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      height: 100vh;
      overflow: hidden;
    }

    header {
      padding: 20px;
      text-align: center;
      border-bottom: 1px solid #ccc;
      flex-shrink: 0;
    }

    .logo img {
      width: 150px;
    }

    main {
      padding: 20px 40px;
      flex: 1;
      overflow-y: auto;
    }

    h2 {
      margin-bottom: 30px;
      font-size: 32px;
      font-weight: normal;
    }

    form label {
      display: block;
      margin: 15px 0 5px;
      font-weight: bold;
      font-size: 14px;
    }

    form input[type="text"],
    form input[type="password"],
    form input[type="date"],
    form input[type="email"],
    form select {
      width: 100%;
      padding: 6px;
      font-size: 14px;
      border: 1px solid #000;
    }

    .form-row {
      display: flex;
      gap: 20px;
      margin-top: 10px;
      align-items: center;
    }

    .form-row>div {
      flex: 1;
    }

    .save-btn {
      background-color: #c0e8c2;
      color: black;
      padding: 10px 20px;
      border: none;
      margin-top: 20px;
      font-size: 14px;
      cursor: pointer;
    }

    footer {
      padding: 20px 40px;
      border-top: 1px solid #ccc;
      font-size: 14px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-shrink: 0;
    }

    .social i {
      margin: 0 10px;
      font-size: 18px;
      color: black;
    }
  </style>
</head>

<body>
  <div class="sidebar">
    <div>
      <h4>DASHBOARD</h4>
      <a href="ADMIN_Dashboard.php">Product</a>
      <a href="ADMIN_Orders.php">Order</a>
      <a href="ADMIN_Browse.php">Browse</a>
      <h4>ACCOUNT</h4>
      <a href="ADMIN_ManageUsers.php" class="active">Manage Users</a>
      <h4>LOGS</h4>
      <a href="ADMIN_SecurityLogs.php">Security Logs</a>
    </div>
    <div class="logout">
      <i class="fa-solid fa-right-from-bracket"></i>
      <a href="login.php">Log Out</a>
    </div>
  </div>

  <div class="main-content">
    <header>
      <div class="logo">
        <img src="../Logos/KW Logo.png" alt="KALYE WEST">
      </div>
    </header>

    <main>
      <form method="POST">
        <div style="background: #f5f5f5; padding: 15px; margin-bottom: 20px; font-weight: bold; text-align: center;">EDIT USER</div>
        <?php if (!empty($_SESSION['errors'])) : ?>
          <ul style="color:red; margin-bottom: 20px;">
            <?php foreach ($_SESSION['errors'] as $error) echo "<li>$error</li>"; ?>
          </ul>
          <?php unset($_SESSION['errors']); ?>
        <?php endif; ?>
        <div class="form-row">
          <div>
            <label for="firstName">First Name:</label>
            <input type="text" id="firstName" name="firstName" value="<?= htmlspecialchars($user['FirstName']) ?>" required>
          </div>
          <div>
            <label for="lastName">Last Name:</label>
            <input type="text" id="lastName" name="lastName" value="<?= htmlspecialchars($user['LastName']) ?>" required>
          </div>
        </div>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['Email']) ?>" required>

        <label for="address">Address:</label>
        <input type="text" id="address" name="address" value="<?= htmlspecialchars($user['Address']) ?>">

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" value="<?= htmlspecialchars($user['Password']) ?>">

        <label for="role">Role:</label>
        <select id="role" name="role" required>
          <?php
          $roles = ['Admin', 'Staff', 'Customer'];
          foreach ($roles as $r) {
            $selected = ($user['Role'] === $r) ? 'selected' : '';
            echo "<option value='$r' $selected>$r</option>";
          }
          ?>
        </select>

        <label for="joined">Joined Date:</label>
        <input type="date" id="joined" name="joined" value="<?= substr($user['Created_At'], 0, 10) ?>" required>
        <button class="save-btn" type="submit">Save Changes</button>
      </form>
    </main>

    <footer>
      <div class="social">
        <a href="#"><i class="fa-brands fa-facebook"></i></a>
        <a href="#"><i class="fa-brands fa-instagram"></i></a>
        <a href="#"><i class="fa-brands fa-tiktok"></i></a>
      </div>
      <div>2025, KALYE WEST</div>
    </footer>
  </div>
</body>

</html>