<?php
session_start();

$host = "localhost";
$user = "root";
$password = "";
$dbname = "dbadm";

// Connect to DB
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get input
$email = $_POST['Email'];
$enteredPassword = $_POST['Password'];

// Query user
$stmt = $conn->prepare("SELECT userID, Password FROM USERS WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();

   if ($enteredPassword === $row['Password']) {
    $_SESSION['userID'] = $row['userID']; // Store userID in session
    header("Location: view_products.php");// Redirect 
    exit();
    } else {
        echo "<h3>❌ Incorrect password.</h3>";
    }
} else {
    echo "<h3>❌ No user found with that email.</h3>";
}

$stmt->close();
$conn->close();
?>
