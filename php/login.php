<?php
session_start();

// Connect to database
$conn = new mysqli("localhost", "root", "", "dbadm");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = trim($_POST['Email']);
$password = trim($_POST['Password']);

// Fetch user data
$stmt = $conn->prepare("SELECT userID, Password, Role FROM USERS WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();

    // ⚠️ Replace this with password_hash verification in production
    if ($password === $row['Password']) {
        $_SESSION['userID'] = $row['userID'];
        $_SESSION['role'] = $row['Role'];

        // ✅ Role-based redirection
        if ($row['Role'] === 'Admin') {
            header("Location: ADMIN_Dashboard.php");
        } else {
            header("Location: view_products.php");
        }
        exit();
    } else {
        echo "<h3>❌ Incorrect password</h3>";
        echo "<a href='login.html'>Try again</a>";
    }
} else {
    echo "<h3>❌ Email not found</h3>";
    echo "<a href='login.html'>Try again</a>";
}

$stmt->close();
$conn->close();
?>
