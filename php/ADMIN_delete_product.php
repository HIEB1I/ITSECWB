<?php
session_start();
if (!isset($_SESSION['userID']) || $_SESSION['role'] == 'Customer') {
  exit("Access denied.");
}

if (!isset($_POST['productID'])) {
  echo 'Missing productID';
  exit;
}

require_once 'db_connect.php';

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
