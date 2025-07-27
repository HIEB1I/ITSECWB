<?php
session_start();
if (!isset($_SESSION['userID']) || $_SESSION['role'] == 'Customer') {
  exit("Access denied.");
}
require_once 'db_connect.php';

// Auto-generate next user ID
function generateUserID($conn) {
    $result = $conn->query("SELECT userID FROM USERS ORDER BY userID DESC LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        $lastID = intval(substr($row['userID'], 1)) + 1;
        return 'U' . str_pad($lastID, 5, '0', STR_PAD_LEFT);
    }
    return 'U00001';
}

$userID = generateUserID($conn);
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userID = generateUserID($conn);
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 
    $role = $_POST['role'];
    $address = $_POST['address'];
    $createdAt = $_POST['joined'];

    $stmt = $conn->prepare("INSERT INTO USERS (userID, FirstName, LastName, Password, Email, Address, Role, Created_At)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $userID, $firstName, $lastName, $password, $email, $address, $role, $createdAt);

    if ($stmt->execute()) {
        $success = " User added successfully.";
        header("Location: ADMIN_ManageUsers.php");
        exit();
    } else {
        $error = " Failed to add user: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Add User ‒ KALYE WEST</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
  * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html, body {
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

    /* Sidebar */
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
    form input[type="number"],
    form textarea,
    form select {
      width: 100%;
      padding: 8px;
      border: 1px solid #000;
      font-size: 14px;
    }

    form textarea {
      height: 100px;
      resize: none;
    }

    .form-row {
      display: flex;
      gap: 20px;
      margin-top: 10px;
    }

    .form-row > div {
      flex: 1;
    }

    .upload-box {
      border: 1px dashed #333;
      padding: 40px;
      text-align: center;
      margin-top: 10px;
    }

    .upload-box i {
      font-size: 24px;
      margin-bottom: 10px;
    }

    .upload-box small {
      display: block;
      margin-top: 5px;
      color: #555;
      font-size: 12px;
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

    /* Adjust spacing for clean input layout */
    form label {
    display: block;
    margin-top: 15px;
    margin-bottom: 5px;
    font-weight: bold;
    font-size: 14px;
    }

    form input[type="text"],
    form input[type="date"],
    form input[type="number"],
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

    .form-row > div {
    flex: 1;
    }
  </style>
</head>
<body>

<div class="sidebar">
  <div>
    <h4>DASHBOARD</h4>
    <a href="ADMIN_Dashboard.php">Product</a>
    <a href="ADMIN_Orders.php">Order</a>
   <a href="HOME_Homepage.php">Browse</a>
    <h4>ACCOUNT</h4>
    <a href="ADMIN_ManageUsers.php" class="active">Manage Users</a>
  </div>
  <div class="logout">
    <i class="fa-solid fa-right-from-bracket"></i>
    <a href="../php/login.php">Log Out</a>
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
      <div style="background: #f5f5f5; padding: 15px; margin-bottom: 20px; font-weight: bold; text-align: center;">ADD USER</div>

      <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
      <?php elseif ($success): ?>
        <p style="color:green;"><?= htmlspecialchars($success) ?></p>
      <?php endif; ?>

      <div class="form-row">
        <label style="font-weight: bold;">User ID:</label>
        <p style="margin-left: 10px;"><?= htmlspecialchars($userID) ?></p>
      </div>

      <div class="form-row">
        <div>
          <label for="firstName">First Name:</label>
          <input type="text" id="firstName" name="firstName" required>
        </div>
        <div>
          <label for="lastName">Last Name:</label>
          <input type="text" id="lastName" name="lastName" required>
        </div>
      </div>

      <label for="email">Email:</label>
      <input type="text" id="email" name="email" required>

      <label for="password">Password:</label>
      <input type="password" id="password" name="password" required>


      <label for="address">Address:</label>
      <input type="text" id="address" name="address" required>

      <label for="role">Role:</label>
      <select id="role" name="role" required>
        <option value="">Select Role</option>
        <option value="Admin">Admin</option>
        <option value="Staff">Staff</option>
        <option value="Customer">Customer</option>
      </select>

      <label for="joined">Joined Date:</label>
      <input type="date" id="joined" name="joined" required>

      <button type="submit" class="save-btn">SAVE</button>
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
