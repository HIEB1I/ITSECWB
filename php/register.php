<?php
session_start();
require 'validation.php';

// Define database credentials
$host = "localhost";
$user = "root";
$password = "";
$dbname = "dbadm";

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Auto-generate userID like U00001, U00002, etc.
$sql = "SELECT userID FROM USERS ORDER BY userID DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $lastID = $row['userID'];
    $num = (int)substr($lastID, 1);  // Remove the 'U' and convert to int
    $num++;
    $userID = 'U' . str_pad($num, 5, '0', STR_PAD_LEFT);
} else {
    $userID = 'U00001';  // First user
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $passwordRaw = $_POST['password'];
    $email = $_POST['email'];
    $role = 'Customer'; 

    // DATA VALIDATION: validation checks & compile
    $errors = [];

    if (!validateString($firstName, 1, 50)) {
        $errors[] = "First name must be between 1 and 50 characters.";
    }
    if (!validateString($lastName, 1, 50)) {
        $errors[] = "Last name must be between 1 and 50 characters.";
    }
    if (!validateString($passwordRaw, 6, 255)) {
        $errors[] = "Password must be between 6 and 255 characters.";
    }

    if (!empty($errors)) {
      $_SESSION['errors'] = $errors;
      header("Location: register.php");
      exit;
  }

    // Prepare SQL and bind parameters
    $password = password_hash($passwordRaw, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO USERS (userID, FirstName, LastName, Password, Email, Role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $userID, $firstName, $lastName, $password, $email, $role);

    if ($stmt->execute()) {
        header("Location: login.php");
        exit();
    } else {
        echo "<h3>Error: " . $stmt->error . "</h3>";
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