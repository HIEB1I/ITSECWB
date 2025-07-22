<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['Email'];
    $password = $_POST['Password'];

    $stmt = $conn->prepare("SELECT userID, Password, Role FROM USERS WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result && $user = $result->fetch_assoc()) {
        if (password_verify($password, $user['Password'])) {
            $_SESSION['userID'] = $user['userID'];
            $_SESSION['role'] = $user['Role'];

            if ($user['Role'] === 'Admin') {
                header("Location: ADMIN_Dashboard.php");
            } elseif ($user['Role'] === 'Staff') {
                header("Location: STAFF_Page.php");
            } else {
                header("Location: Customer_Home.php");
            }
            exit;
        } else {
            echo "Incorrect password.";
        }
    } else {
        echo "No user found with that email.";
    }

    $stmt->close();
    $conn->close();
}
?>
