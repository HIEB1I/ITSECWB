<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['userID'])) {
    exit("Access denied.");
}

require_once 'db_connect.php'; 

// Get user session ID
$userID = $_SESSION['userID'];

// Validate cartItemsID input
if (!isset($_POST['cartItemsID']) || empty($_POST['cartItemsID'])) {
    exit("Missing cart item ID.");
}

$cartItemID = $_POST['cartItemsID'];

// Determine action: update or delete
if (isset($_POST['delete'])) {
    // Delete the item
    $stmt = $conn->prepare("DELETE FROM CART_ITEMS WHERE cartItemsID = ?");
    $stmt->bind_param("s", $cartItemID);
    $stmt->execute();
    $stmt->close();

} elseif (isset($_POST['update'])) {
    // Check quantity is valid
    if (!isset($_POST['Quantity']) || (int)$_POST['Quantity'] < 1) {
        exit("Invalid quantity.");
    }

    $newQty = (int)$_POST['Quantity'];

    // Update quantity
    $stmt = $conn->prepare("UPDATE CART_ITEMS SET QuantityOrdered = ? WHERE cartItemsID = ?");
    $stmt->bind_param("is", $newQty, $cartItemID);
    $stmt->execute();
    $stmt->close();
}

$conn->close();

// Redirect back to view cart
header("Location: view_cart.php");
exit();
?>
