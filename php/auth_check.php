<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function requireLogin() {
    if (!isset($_SESSION['userID'])) {
        exit("Access denied.");
    }
}

// Check if user has required roles
function requireRole(array $roles) {
    requireLogin();
    if (!in_array($_SESSION['role'], $roles)) {
        header("Location: login.php"); // fails securely
        exit();
    }
}
?>
