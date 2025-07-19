<?php
session_start();
if (!isset($_SESSION['userID'])) {
    exit("Access denied.");
}

require_once 'db_connect.php'; 

$userID = $_SESSION['userID'];
$productID = $_POST['productID'];
$quantity = (int)$_POST['Quantity']; // Force quantity to be integer

$conn->autocommit(FALSE); // Begin transaction

try {
    // 🔹 Step 1: Get or create active cart
    $stmt = $conn->prepare("SELECT cartID FROM CART WHERE ref_userID = ? AND Purchased = FALSE");
    $stmt->bind_param("s", $userID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $cartID = $result->fetch_assoc()['cartID'];
    } else {
        // Generate new cart ID
        $res = $conn->query("SELECT cartID FROM CART ORDER BY cartID DESC LIMIT 1");
        $lastID = $res->num_rows > 0 ? $res->fetch_assoc()['cartID'] : 'C00000';
        $nextNum = (int)substr($lastID, 1) + 1;
        $cartID = 'C' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);

        // Insert new cart
        $insertCart = $conn->prepare("INSERT INTO CART (cartID, Total, Purchased, ref_userID) VALUES (?, 0, FALSE, ?)");
        $insertCart->bind_param("ss", $cartID, $userID);
        if (!$insertCart->execute()) {
            throw new Exception("Failed to create new cart.");
        }
        $insertCart->close();
    }
    $stmt->close();

    // 🔹 Step 2: Generate new cartItemsID
    $res = $conn->query("SELECT cartItemsID FROM CART_ITEMS ORDER BY cartItemsID DESC LIMIT 1");
    $lastCI = $res->num_rows > 0 ? $res->fetch_assoc()['cartItemsID'] : 'CI00000';
    $nextCI = (int)substr($lastCI, 2) + 1;
    $cartItemsID = 'CI' . str_pad($nextCI, 5, '0', STR_PAD_LEFT);

    // 🔹 Step 3: Insert cart item
    $addItem = $conn->prepare("INSERT INTO CART_ITEMS (cartItemsID, QuantityOrdered, ref_productID, ref_cartID) VALUES (?, ?, ?, ?)");
    $addItem->bind_param("siss", $cartItemsID, $quantity, $productID, $cartID);
    if (!$addItem->execute()) {
        throw new Exception("Failed to add item to cart.");
    }
    $addItem->close();

    // ✅ Step 4: Commit transaction
    $conn->commit();

    echo "<h3>✅ Item added to cart!</h3>";
    echo "<a href='view_products.php'>⬅ Back to Products</a> | <a href='view_cart.php'>🛒 View Cart</a>";

} catch (Exception $e) {
    $conn->rollback(); // ❌ Rollback on failure
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
}

$conn->close();
?>
