<?php
session_start();

$host = "localhost";
$dbname = "dbadm";

// Default fallback credentials
$db_user = "customer_user";

// Assign based on session role
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
    }
}

// Connect to the database
$conn = new mysqli($host, $db_user, "", $dbname); 

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
