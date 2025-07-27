<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in (this should be implemented in your session management)
if (!isset($_SESSION['userID'])) {
    die('You must be logged in to add products to the cart.');
}

$userID = $_SESSION['userID']; // Assuming the userID is stored in the session
$productID = $_POST['productID']; // Get the product ID from the form
$quantity = $_POST['Quantity']; // Get the quantity from the form

//Check if the user already has an active cart (Purchased = FALSE)
$stmt_check_cart = $conn->prepare("SELECT cartID FROM CART WHERE ref_userID = ? AND Purchased = FALSE LIMIT 1");
$stmt_check_cart->bind_param("s", $userID);
$stmt_check_cart->execute();
$result = $stmt_check_cart->get_result();
$cart = $result->fetch_assoc();

if (!$cart) {
    //If no active cart exists, create a new cart
    $stmt_create_cart = $conn->prepare("CALL create_new_cart(?)");
    $stmt_create_cart->bind_param("s", $userID);
    if ($stmt_create_cart->execute()) {
        //Fetch the newly created cartID
        $stmt_get_cartID = $conn->prepare("SELECT CONCAT('C', LPAD(IFNULL(MAX(SUBSTRING(cartID, 2)) + 1, 1), 5, '0')) AS new_cartID FROM CART;");
        $stmt_get_cartID->execute();
        $result = $stmt_get_cartID->get_result();
        $new_cart = $result->fetch_assoc();
        $cartID = $new_cart['new_cartID'];
    } else {
        die('Failed to create a new cart.');
    }
} else {
    $cartID = $cart['cartID']; // Use the existing cartID
}

//Add the product to the CART_ITEMS table
$stmt_add_to_cart = $conn->prepare("INSERT INTO CART_ITEMS (ref_cartID, ref_productID, QuantityOrdered) VALUES (?, ?, ?)");
$stmt_add_to_cart->bind_param("ssi", $cartID, $productID, $quantity);

if ($stmt_add_to_cart->execute()) {
    //Update the cart's total after adding the product
    $stmt_update_total = $conn->prepare("CALL update_cart_total(?)");
    $stmt_update_total->bind_param("s", $cartID);
    if ($stmt_update_total->execute()) {
        echo 'Product added to cart successfully!';
    } else {
        echo 'Failed to update cart total.';
    }
} else {
    echo 'Failed to add product to cart.';
}

$conn->close();
?>
