<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}

if (isset($_POST['update']) || isset($_POST['delete'])) {
    $cartItemsID = $_POST['cartItemsID'];
    
    // Get cartID for the item
    $stmt = $conn->prepare("SELECT ref_cartID FROM CART_ITEMS WHERE cartItemsID = ?");
    $stmt->bind_param("s", $cartItemsID);
    $stmt->execute();
    $cartID = $stmt->get_result()->fetch_assoc()['ref_cartID'];
    $stmt->close();

    if (isset($_POST['delete'])) {
        $stmt = $conn->prepare("DELETE FROM CART_ITEMS WHERE cartItemsID = ?");
        $stmt->bind_param("s", $cartItemsID);
        $stmt->execute();
        $stmt->close();
    } else {
        $quantity = $_POST['Quantity'];
        $stmt = $conn->prepare("UPDATE CART_ITEMS SET QuantityOrdered = ? WHERE cartItemsID = ?");
        $stmt->bind_param("is", $quantity, $cartItemsID);
        $stmt->execute();
        $stmt->close();
    }

    // Update cart total using stored procedure
    $stmt = $conn->prepare("CALL update_cart_total(?)");
    $stmt->bind_param("s", $cartID);
    $stmt->execute();
    $stmt->close();
    $conn->next_result();
}

header('Location: CART_ViewCart.php');
exit();
?>
