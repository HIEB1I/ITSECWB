<?php
session_start();
require_once 'db_connect.php';
require 'validation.php';

// Auto-generate userID
$sql = "SELECT userID FROM USERS ORDER BY userID DESC LIMIT 1";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $lastID = $row['userID'];
    $num = (int)substr($lastID, 1);
    $num++;
    $userID = 'U' . str_pad($num, 5, '0', STR_PAD_LEFT);
} else {
    $userID = 'U00001';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = trim($_POST['firstName']);
    $lastName  = trim($_POST['lastName']);
    $email     = trim($_POST['email']);
    
    
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


    $role = 'Customer';
    
    // DATA VALIDATION: validation checks & compile
    $errors = [];

    if (!validateString($firstName, 1, 50)) {
        $errors[] = "First name must be between 1 and 50 characters.";
    }
    if (!validateString($lastName, 1, 50)) {
        $errors[] = "Last name must be between 1 and 50 characters.";
    }

    if (!empty($errors)) {
      $_SESSION['errors'] = $errors;
      header("Location: register.php");
      exit;
    }

    $stmt = $conn->prepare("INSERT INTO USERS (userID, FirstName, LastName, Password, Email, Role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $userID, $firstName, $lastName, $password, $email, $role);

    if ($stmt->execute()) {
        header("Location: login.php");
        exit();
    } else {
        echo "<h3>Registration failed. Please try again.</h3>"; // Generic fail (#2)
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Account - Clothing Store</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      text-align: center;
      background-color: white;
      margin: 0;
      padding: 0;
    }

    .logo {
      margin-top: 5px;
      margin-bottom: 5px;
    }

    .logo img {
      width: 400px;
      height: auto;
    }

    hr {
      width: 90%;
      margin: 5px auto;
    }

    .register-container {
      max-width: 400px;
      margin: auto;
    }

    h2 {
      font-size: 22px;
      margin: 20px 0;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 12px;
      margin: 8px 0;
      box-sizing: border-box;
      border: 1px solid #000;
    }

    .create-btn {
      background-color: black;
      color: white;
      border: none;
      padding: 10px 30px;
      margin: 20px 0;
      cursor: pointer;
    }

    .footer-logos {
      display: flex;
      justify-content: space-around;
      align-items: center;
      flex-wrap: wrap;
      margin-top: 122px;
      padding: 20px;
      border-top: 1px solid #ccc;
    }

    .footer-logos img {
      height: 40px;
      max-width: 100px;
      margin: 10px;
      object-fit: contain;
    }

    a {
      color: black;
      text-decoration: none;
      font-size: 12px;
    }
  </style>
</head>
<body>

    <a href="login.php" style="position: absolute; top: 20px; left: 50px; text-decoration: none; font-size: 30px;">←</a>

  <!-- KW Logo -->
  <div class="logo">
    <img src="../Logos/KW Logo.png" alt="KALYE WEST">
  </div>

  <hr>

  <!-- Signup Form -->
  <div class="register-container">
    <h2>Create account</h2>
    <form method="post" action="">

    <!-- DATA VALIDATION: display compiled errors -->
    <?php if (!empty($_SESSION['errors'])): ?>
      <ul style="color:red;">
        <?php foreach ($_SESSION['errors'] as $error) echo "<li>$error</li>"; ?>
      </ul>
        <?php unset($_SESSION['errors']); ?>
    <?php endif; ?>
    
      <input type="text" name="firstName" placeholder="First Name" required>
      <input type="text" name="lastName" placeholder="Last Name" required>
      <input type="email" name="email" placeholder="Email Address" required>
      <input type="password" name="password" placeholder="Password" required>
      <button class="create-btn" type="submit">Create</button>
    </form>
  </div>

  <!-- Footer Logos -->
  <div class="footer-logos">
    <img src="../Logos/Femme Logo.png" alt="FEMME">
    <img src="../Logos/Little Logo.png" alt="little human">
    <img src="../Logos/MNLA Logo.png" alt="MN+LA">
    <img src="../Logos/Sage Logo.png" alt="SageHill.">
    <img src="../Logos/Daily Logo.png" alt="Daily Flight">
  </div>
</body>
</html>