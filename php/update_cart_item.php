<?php
session_start();

if (!isset($_SESSION['userID'])) {
    exit("Access denied.");
}

require_once 'db_connect.php'; 

$userID = $_SESSION['userID'];

if (!isset($_POST['cartItemsID']) || empty($_POST['cartItemsID'])) {
    exit("Missing cart item ID.");
}

$cartItemID = $_POST['cartItemsID'];

// Fetch current quantity
$stmt = $conn->prepare("SELECT QuantityOrdered FROM CART_ITEMS WHERE cartItemsID = ? AND ref_cartID IN (SELECT cartID FROM CART WHERE ref_userID = ? AND Purchased = FALSE)");
$stmt->bind_param("si", $cartItemID, $userID);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    exit("Item not found.");
}
$currentRow = $result->fetch_assoc();
$currentQty = (int)$currentRow['QuantityOrdered'];
$stmt->close();

// Determine action
if (isset($_POST['delete'])) {
    $stmt = $conn->prepare("DELETE FROM CART_ITEMS WHERE cartItemsID = ?");
    $stmt->bind_param("s", $cartItemID);
    $stmt->execute();
    $stmt->close();

} elseif (isset($_POST['increase'])) {
    $newQty = $currentQty + 1;

    $stmt = $conn->prepare("UPDATE CART_ITEMS SET QuantityOrdered = ? WHERE cartItemsID = ?");
    $stmt->bind_param("is", $newQty, $cartItemID);
    $stmt->execute();
    $stmt->close();

} elseif (isset($_POST['decrease'])) {
    $newQty = max(1, $currentQty - 1); // Minimum quantity of 1

    $stmt = $conn->prepare("UPDATE CART_ITEMS SET QuantityOrdered = ? WHERE cartItemsID = ?");
    $stmt->bind_param("is", $newQty, $cartItemID);
    $stmt->execute();
    $stmt->close();

} elseif (isset($_POST['update'])) {
    // Optional: add logic to confirm final value, or redirect without change
    // For now, do nothing extra unless you add a visible input for new quantity
}

$conn->close();

// Redirect back to cart
header("Location: CART_ViewCart.php");
exit();
?>
