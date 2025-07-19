<?php
session_start();
if (!isset($_SESSION['userID'])) {
    exit("Access denied.");
}

require_once 'db_connect.php'; 

$userID = $_SESSION['userID'];

$conn->autocommit(FALSE); // Begin transaction



try {
    // Step 1: Get the user's cart
    $stmt = $conn->prepare("SELECT cartID FROM CART WHERE ref_userID = ? AND Purchased = FALSE");
    $stmt->bind_param("s", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) throw new Exception("No active cart found.");

    $cartID = $result->fetch_assoc()['cartID'];

    // Step 2: Check stock for all items in the cart
    $stockCheck = $conn->query("
        SELECT CI.ref_productID, CI.QuantityOrdered, P.QuantityAvail, P.ProductName
        FROM CART_ITEMS CI
        JOIN PRODUCT P ON CI.ref_productID = P.productID
        WHERE CI.ref_cartID = '$cartID'
    ");

    while ($row = $stockCheck->fetch_assoc()) {
        if ((int)$row['QuantityOrdered'] > (int)$row['QuantityAvail']) {
            throw new Exception("❌ Not enough stock for '{$row['ProductName']}'");
        }
    }

    // Step 3: Mark cart as purchased
    $update = $conn->prepare("UPDATE CART SET Purchased = TRUE WHERE cartID = ?");
    $update->bind_param("s", $cartID);
    if (!$update->execute()) throw new Exception("Failed to update cart.");

    // Step 4: Deduct stock
    $stockUpdate = $conn->query("
        UPDATE PRODUCT P
        JOIN CART_ITEMS CI ON P.productID = CI.ref_productID
        SET P.QuantityAvail = P.QuantityAvail - CI.QuantityOrdered
        WHERE CI.ref_cartID = '$cartID'
    ");
    if (!$stockUpdate) throw new Exception("Failed to update product stock.");

    // ✅ Step 5: Commit if all successful
    $conn->commit();
    echo "<h3>✅ Checkout successful!</h3>";
    echo "<a href='view_products.php'>Shop Again</a>";

} catch (Exception $e) {
    $conn->rollback(); // ❌ Rollback if any step fails
    echo "<h3>❌ Checkout failed: " . $e->getMessage() . "</h3>";
    echo "<a href='view_products.php' style='display:inline-block; margin-top:10px; padding:8px 12px; background-color:#4CAF50; color:white; text-decoration:none; border-radius:5px;'>⬅ Back to Products</a>";
}


$conn->close();
?>
