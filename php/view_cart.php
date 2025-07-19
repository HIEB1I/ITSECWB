<?php
session_start();
if (!isset($_SESSION['userID'])) {
    exit("Access denied.");
}

require_once 'db_connect.php'; 

$userID = $_SESSION['userID'];

// ✅ Get active cart
$stmt = $conn->prepare("SELECT cartID FROM CART WHERE ref_userID = ? AND Purchased = FALSE");
$stmt->bind_param("s", $userID); 
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<h3>Your cart is empty.</h3><a href='view_products.php'>⬅ Browse Products</a>";
    exit();
}

$cartID = $result->fetch_assoc()['cartID'];
$stmt->close();

// ✅ Get cart items
$sql = "SELECT CI.cartItemsID, P.ProductName, P.Price, CI.QuantityOrdered,
               (P.Price * CI.QuantityOrdered) AS SubTotal
        FROM CART_ITEMS CI
        JOIN PRODUCT P ON CI.ref_productID = P.productID
        WHERE CI.ref_cartID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $cartID);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
echo "<h2>Your Cart</h2>";
echo "<table border='1' cellpadding='5'>
<tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th>Action</th></tr>";

while ($row = $result->fetch_assoc()) {
    $total += $row['SubTotal'];
    $formattedPrice = number_format($row['Price'], 2);
    $formattedSubtotal = number_format($row['SubTotal'], 2);
    
    echo "<tr>
        <form action='update_cart_item.php' method='post'>
        <td>{$row['ProductName']}</td>
        <td>₱{$formattedPrice}</td>
        <td>
            <input type='number' name='Quantity' min='1' value='{$row['QuantityOrdered']}' required>
            <input type='hidden' name='cartItemsID' value='{$row['cartItemsID']}'>
        </td>
        <td>₱{$formattedSubtotal}</td>
        <td>
            <button type='submit' name='update'>Update</button>
            <button type='submit' name='delete' onclick=\"return confirm('Remove this item?')\">Delete</button>
        </td>
        </form>
    </tr>";
}
echo "</table>";

echo "<h3>Total: ₱" . number_format($total, 2) . "</h3>";

// ✅ Update total in CART table (prepared statement to avoid SQL injection)
$update = $conn->prepare("UPDATE CART SET Total = ? WHERE cartID = ?");
$update->bind_param("ds", $total, $cartID);
$update->execute();
$update->close();

// ✅ Checkout form
echo "<form action='checkout.php' method='post'>
        <input type='submit' value='✅ Checkout Now'>
      </form>";

echo "<br><a href='view_products.php'>⬅ Continue Shopping</a>";

$conn->close();
?>
