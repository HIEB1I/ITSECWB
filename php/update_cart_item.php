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

// Fetch current quantity AND cartID
$stmt = $conn->prepare("
    SELECT CI.QuantityOrdered, CI.ref_cartID 
    FROM CART_ITEMS CI 
    JOIN CART C ON CI.ref_cartID = C.cartID 
    WHERE CI.cartItemsID = ? AND C.ref_userID = ? AND C.Purchased = FALSE
");
$stmt->bind_param("ss", $cartItemID, $userID);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    exit("Item not found.");
}
$currentRow = $result->fetch_assoc();
$currentQty = (int)$currentRow['QuantityOrdered'];
$cartID = $currentRow['ref_cartID']; // Get the cartID for later use
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

// After updating cart items - now $cartID is properly defined
$stmt = $conn->prepare("CALL update_cart_total(?)");
$stmt->bind_param("s", $cartID);
$stmt->execute();
$stmt->close();

$conn->close();

// Redirect back to cart
header("Location: CART_ViewCart.php");
exit();
?>