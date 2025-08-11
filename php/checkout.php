<?php
// Any Role
require_once 'auth_check.php';
requireLogin(); // any logged-in role
require_once 'db_connect.php';


$userID = $_SESSION['userID'];
$conn->autocommit(FALSE); // Manual commit/rollback

try {
    // Set highest isolation level to ensure safe concurrent access
    $conn->query("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");

    // Start transaction
    $conn->begin_transaction();

    // Step 1: Get active cart for user
    $stmt = $conn->prepare("SELECT cartID FROM CART WHERE ref_userID = ? AND Purchased = FALSE");
    $stmt->bind_param("s", $userID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("No active cart found.");
    }

    $cartID = $result->fetch_assoc()['cartID'];
    $stmt->close();

    // Step 2: Check stock availability for each product in the cart
    $checkStockSQL = "
        SELECT CI.ref_productID, CI.QuantityOrdered, P.QuantityAvail, P.ProductName
        FROM CART_ITEMS CI
        JOIN PRODUCT P ON CI.ref_productID = P.productID
        WHERE CI.ref_cartID = ?
    ";
    $checkStmt = $conn->prepare($checkStockSQL);
    $checkStmt->bind_param("s", $cartID);
    $checkStmt->execute();
    $stockResult = $checkStmt->get_result();

    $insufficientStock = [];

    while ($row = $stockResult->fetch_assoc()) {
        if ((int)$row['QuantityOrdered'] > (int)$row['QuantityAvail']) {
            $insufficientStock[] = $row['ProductName'];
        }
    }

    $checkStmt->close();

    if (!empty($insufficientStock)) {
        $productList = implode(', ', $insufficientStock);
        throw new Exception("❌ Not enough stock for: $productList.");
    }

    // Step 3: Validate payment method and currency
    $currency = $_POST['currency'] ?? '';
    $mop = $_POST['payment_method'] ?? '';

    if (!in_array($currency, ['PHP', 'USD', 'WON']) || !in_array($mop, ['COD', 'GCash', 'Card'])) {
        throw new Exception("Invalid Currency or Mode of Payment.");
    }

    // Step 4: Update the CART table
    $updateCart = $conn->prepare("
        UPDATE CART 
        SET Currency = ?, MOP = ?, Purchased = TRUE, Status = 'To Ship' 
        WHERE cartID = ?
    ");
    $updateCart->bind_param("sss", $currency, $mop, $cartID);
    if (!$updateCart->execute()) {
        throw new Exception("Failed to update cart details.");
    }
    $updateCart->close();

    // Step 5: Deduct product stock based on ordered quantity
    $deductStockSQL = "
        UPDATE PRODUCT P
        JOIN CART_ITEMS CI ON P.productID = CI.ref_productID
        SET P.QuantityAvail = P.QuantityAvail - CI.QuantityOrdered
        WHERE CI.ref_cartID = ?
    ";
    $deductStmt = $conn->prepare($deductStockSQL);
    $deductStmt->bind_param("s", $cartID);
    if (!$deductStmt->execute()) {
        throw new Exception("Failed to update product stock.");
    }
    $deductStmt->close();

    // Step 6: Commit transaction
    $conn->commit();

    echo "<h3>✅ Checkout successful!</h3>";
    echo "<a href='HOME_Homepage.php'>Shop Again</a>";

} catch (Exception $e) {
    // Step 7: Rollback all changes if any error occurred
    $conn->rollback();
    echo "<h3>❌ Checkout failed: " . htmlspecialchars($e->getMessage()) . "</h3>";
    echo "<a href='HOME_Homepage.php' style='display:inline-block; margin-top:10px; padding:8px 12px; background-color:#4CAF50; color:white; text-decoration:none; border-radius:5px;'>⬅ Back to Products</a>";
}

$conn->close();
?>
