<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['userID'])) {
    exit("Access denied.");
}

require_once 'db_connect.php'; 

$userID = $_SESSION['userID'];

// Begin transaction
$conn->autocommit(FALSE);

try {
    // 🔹 Step 1: Get active cart
    $stmt = $conn->prepare("SELECT cartID FROM CART WHERE ref_userID = ? AND Purchased = FALSE");
    $stmt->bind_param("s", $userID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("No active cart found.");
    }

    $cartID = $result->fetch_assoc()['cartID'];
    $stmt->close();

    // 🔹 Step 2: Check stock
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

    while ($row = $stockResult->fetch_assoc()) {
        if ((int)$row['QuantityOrdered'] > (int)$row['QuantityAvail']) {
            throw new Exception("❌ Not enough stock for '{$row['ProductName']}'");
        }
    }
    $checkStmt->close();

    // 🔹 Step 3: Mark cart as purchased
    $updateCart = $conn->prepare("UPDATE CART SET Purchased = TRUE WHERE cartID = ?");
    $updateCart->bind_param("s", $cartID);
    if (!$updateCart->execute()) {
        throw new Exception("Failed to mark cart as purchased.");
    }
    $updateCart->close();

    // 🔹 Step 4: Deduct stock
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

    // ✅ Step 5: Commit
    $conn->commit();
    echo "<h3>✅ Checkout successful!</h3>";
    echo "<a href='view_products.php'>Shop Again</a>";

} catch (Exception $e) {
    // ❌ Step 6: Rollback
    $conn->rollback();
    echo "<h3>❌ Checkout failed: " . $e->getMessage() . "</h3>";
    echo "<a href='view_products.php' style='display:inline-block; margin-top:10px; padding:8px 12px; background-color:#4CAF50; color:white; text-decoration:none; border-radius:5px;'>⬅ Back to Products</a>";
}

$conn->close();
?>
