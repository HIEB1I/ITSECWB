<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'security_logger.php';
require_once 'db_connect.php';

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
        $conn = new mysqli("localhost", "root", "", "dbadm");
        $logger = new SecurityLogger($conn);
        $logger->logAccessControlFailureDirect(
            basename($_SERVER['PHP_SELF']),
            implode('/', $roles),
            $_SESSION['role'],
            $_SESSION['userID']
        );
        $conn->close();
        header("Location: error_pages/security_blocked.php");
        exit();
    }
}
?>
