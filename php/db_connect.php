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
            exit("Access denied."); 
    }
} else { 
    // Allow public access to register.php and forgot_password.php
    if (in_array(basename($_SERVER['PHP_SELF']), ['register.php', 'forgot_password.php'])) {
        $db_user = "public_user"; // low privilege user for registration
    } else {
        exit("Access denied.");
    }
}

$conn = new mysqli($host, $db_user, "", $dbname);

// Fail securely if DB connection fails
if ($conn->connect_error) {
    error_log("DB Connection failed: " . $conn->connect_error); // Log internally
    exit("Service unavailable."); 
}
?>
