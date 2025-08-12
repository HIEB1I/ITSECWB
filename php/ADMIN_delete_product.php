<?php
// Admin page access
require_once 'auth_check.php';
requireRole(['Admin']); // only admins allowed
require_once 'db_connect.php';

if (!isset($_POST['productID'])) {
  echo 'Missing productID';
  exit;
}

$productID = $_POST['productID'];

$stmt = $conn->prepare("DELETE FROM PRODUCT WHERE productID = ?");
$stmt->bind_param("s", $productID);

if ($stmt->execute()) {
  echo 'success';
} else {
  echo 'error';
}

$conn->close();
?>
