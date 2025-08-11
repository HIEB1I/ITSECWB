<?php
// Ensure session exists before role check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$dbname = "dbadm";
$db_user = "";

// Assign DB user based on role 
if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'Admin':
            $db_user = "admin_user";
            break;
        case 'Staff':
            $db_user = "staff_user";
            break;
        case 'Customer':
            $db_user = "customer_user";
            break;
        default:
            exit("Access denied."); // (REQ #2)
    }
} else {
    exit("Access denied."); // (REQ #1 + #2)
}

$conn = new mysqli($host, $db_user, "", $dbname);

// Fail securely if DB connection fails
if ($conn->connect_error) {
    error_log("DB Connection failed: " . $conn->connect_error); // Log internally
    exit("Service unavailable."); // (REQ #2)
}
?>
