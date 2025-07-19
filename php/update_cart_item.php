<?php
session_start();
if (!isset($_SESSION['userID'])) {
    exit("Access denied.");
}

require_once 'db_connect.php'; 

$userID = $_SESSION['userID'];

// Validate input
$cartItemID = $_POST['cartItemsID'];

if (isset($_POST['delete'])) {
    // Delete item
    $stmt = $conn->prepare("DELETE FROM CART_ITEMS WHERE cartItemsID = ?");
    $stmt->bind_param("s", $cartItemID);
    $stmt->execute();
} else if (isset($_POST['update'])) {
    $newQty = $_POST['Quantity'];
    // Update quantity
    $stmt = $conn->prepare("UPDATE CART_ITEMS SET QuantityOrdered = ? WHERE cartItemsID = ?");
    $stmt->bind_param("ss", $newQty, $cartItemID);
    $stmt->execute();
}

// After operation
header("Location: view_cart.php");
exit();
?>
