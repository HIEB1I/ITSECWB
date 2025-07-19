<?php
session_start();
if (!isset($_SESSION['userID'])) {
    exit("Access denied.");
}

$productID = $_POST['productID'];
$quantity = $_POST['Quantity'];

require_once 'db_connect.php'; 

$userID = $_SESSION['userID'];

$conn->autocommit(FALSE); // Start transaction

try {
    // Step 1: Check if the user has an active cart
    $stmt = $conn->prepare("SELECT cartID FROM CART WHERE ref_userID = ? AND Purchased = FALSE");
    $stmt->bind_param("s", $userID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $cartID = $result->fetch_assoc()['cartID'];
    } else {
        // Step 2: Generate new cart ID
        $result = $conn->query("SELECT cartID FROM CART ORDER BY cartID DESC LIMIT 1");
        $lastID = $result->num_rows > 0 ? $result->fetch_assoc()['cartID'] : 'C00000';
        $num = (int)substr($lastID, 1) + 1;
        $cartID = 'C' . str_pad($num, 5, '0', STR_PAD_LEFT);

        // Step 3: Insert new cart
        $insertCart = $conn->prepare("INSERT INTO CART (cartID, Total, Purchased, ref_userID) VALUES (?, 0, FALSE, ?)");
        $insertCart->bind_param("ss", $cartID, $userID);
        if (!$insertCart->execute()) {
            throw new Exception("Failed to create cart.");
        }
    }

    // Step 4: Generate new cartItemsID
    $result = $conn->query("SELECT cartItemsID FROM CART_ITEMS ORDER BY cartItemsID DESC LIMIT 1");
    $lastCI = $result->num_rows > 0 ? $result->fetch_assoc()['cartItemsID'] : 'CI00000';
    $num = (int)substr($lastCI, 2) + 1;
    $cartItemsID = 'CI' . str_pad($num, 5, '0', STR_PAD_LEFT);

    // Step 5: Insert into CART_ITEMS
    $addItem = $conn->prepare("INSERT INTO CART_ITEMS (cartItemsID, QuantityOrdered, ref_productID, ref_cartID) VALUES (?, ?, ?, ?)");
    $addItem->bind_param("ssss", $cartItemsID, $quantity, $productID, $cartID);
    if (!$addItem->execute()) {
        throw new Exception("Failed to add item to cart.");
    }

    // ✅ All successful — commit the transaction
    $conn->commit();
    echo "<h3>✅ Item added to cart!</h3>";
    echo "<a href='view_products.php'>⬅ Back to Products</a> | <a href='view_cart.php'>🛒 View Cart</a>";

} catch (Exception $e) {
    // ❌ Something went wrong — rollback
    $conn->rollback();
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
}

$conn->close();
?>
