<?php
// Admin page access
require_once 'auth_check.php';
requireRole(['Admin']); // only admins allowed
require_once 'db_connect.php';


$userID = $_GET['userID'] ?? null;
if (!$userID) {
  die("❌ No user ID provided.");
}

$error = '';
$user = null;

// Handle update on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $firstName = $_POST['firstName'];
  $lastName = $_POST['lastName'];
  $email = $_POST['email'];
  $address = $_POST['address'];
     // Enforce password complexity & length
    $passwordPlain = $_POST['password'] ?? '';
    $minLength = 8;

    if (
        strlen($passwordPlain) < $minLength ||
        !preg_match('/[A-Z]/', $passwordPlain) ||  // Uppercase
        !preg_match('/[a-z]/', $passwordPlain) ||  // Lowercase
        !preg_match('/[0-9]/', $passwordPlain) ||  // Number
        !preg_match('/[\W]/', $passwordPlain)      // Special char
    ) {
        die("❌ Password must be at least $minLength characters long and include uppercase, lowercase, number, and special character.");
    }
    // Store strong salted hash using password_hash (built-in salt)
    // Hash the password (bcrypt with salt automatically handled)
    $password = password_hash($passwordPlain, PASSWORD_DEFAULT);
  $role = $_POST['role'];
  $joined = $_POST['joined'];

  $stmt = $conn->prepare("UPDATE USERS SET FirstName=?, LastName=?, Email=?, Address=?, Password=?, Role=?, Created_At=? WHERE userID=?");
  $stmt->bind_param("ssssssss", $firstName, $lastName, $email, $address, $password, $role, $joined, $userID);

  if ($stmt->execute()) {
    header("Location: ADMIN_ManageUsers.php");
    exit();
  } else {
    $error = "Failed to update user.";
  }
  $stmt->close();
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
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; font-family: Arial, sans-serif; background: #fff; color: #000; }
    body { display: flex; height: 100vh; overflow: hidden; }
    .sidebar { width: 220px; background: #111; color: white; padding: 40px 20px; display: flex; flex-direction: column; justify-content: space-between; }
    .sidebar h4 { margin: 30px 0 10px; font-weight: bold; font-size: 14px; }
    .sidebar a { color: white; text-decoration: none; margin: 8px 0; display: block; font-size: 14px; transition: 0.2s; }
    .sidebar a.active { text-decoration: underline; text-underline-offset: 4px; }
    .sidebar a:hover { opacity: 0.7; }
    .sidebar .logout { margin-top: 40px; display: flex; align-items: center; gap: 8px; }
    .main-content { flex: 1; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
    header { padding: 20px; text-align: center; border-bottom: 1px solid #ccc; flex-shrink: 0; }
    .logo img { width: 150px; }
    main { padding: 20px 40px; flex: 1; overflow-y: auto; }
    h2 { margin-bottom: 30px; font-size: 32px; font-weight: normal; }
    form label { display: block; margin: 15px 0 5px; font-weight: bold; font-size: 14px; }
    form input[type="text"],
    form input[type="password"],
    form input[type="date"],
    form input[type="email"],
    form select { width: 100%; padding: 6px; font-size: 14px; border: 1px solid #000; }
    .form-row { display: flex; gap: 20px; margin-top: 10px; align-items: center; }
    .form-row > div { flex: 1; }
    .save-btn { background-color: #c0e8c2; color: black; padding: 10px 20px; border: none; margin-top: 20px; font-size: 14px; cursor: pointer; }
    footer { padding: 20px 40px; border-top: 1px solid #ccc; font-size: 14px; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
    .social i { margin: 0 10px; font-size: 18px; color: black; }
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
        <input type="text" id="address" name="address" value="<?= htmlspecialchars($user['Address']) ?>" required>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" value="<?= htmlspecialchars($user['Password']) ?>" required>

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

        <?php if ($error): ?>
          <p style="color:red;"><?= $error ?></p>
        <?php endif; ?>

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
