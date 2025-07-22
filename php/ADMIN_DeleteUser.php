<?php
session_start();
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'Admin') {
    exit("Access denied.");
}
require_once 'db_connect.php';

if (!isset($_GET['userID'])) {
    exit('❌ No user ID specified.');
}

$userID = $_GET['userID'];

// 🔒 OPTIONAL: validate user ID format
if (!preg_match('/^U\d{5}$/', $userID)) {
    exit('❌ Invalid user ID format.');
}

// ✅ Prepare and execute deletion
$stmt = $conn->prepare("DELETE FROM USERS WHERE userID = ?");
$stmt->bind_param("s", $userID);

if ($stmt->execute()) {
    // ✅ Successfully deleted
    header("Location: ADMIN_ManageUsers.php");
    exit();
} else {
    echo "❌ Failed to delete user: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
