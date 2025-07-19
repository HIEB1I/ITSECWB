<?php
session_start();

// Use base credentials to query USERS table
$conn = new mysqli("localhost", "root", "", "dbadm");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = $_POST['Email'];
$password = $_POST['Password'];

// Fetch user info
$stmt = $conn->prepare("SELECT userID, Password, Role FROM USERS WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();

    // Simple password check (improve later with hashing)
    if ($password === $row['Password']) {
        // Store session data
        $_SESSION['userID'] = $row['userID'];
        $_SESSION['role'] = $row['Role'];

        // ✅ Now redirect to a protected page that uses db_connect.php
        header("Location: view_products.php");
        exit();
    } else {
        echo "<h3>❌ Incorrect password</h3>";
    }
} else {
    echo "<h3>❌ Email not found</h3>";
}

$stmt->close();
$conn->close();
?>
