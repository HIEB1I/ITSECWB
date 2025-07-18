<?php
session_start();
if (!isset($_SESSION['userID'])) {
    echo "Access denied.";
    exit();
}

$ref_userID = $_SESSION['userID'];

$host = "localhost";
$user = "root";
$password = "";
$dbname = "dbadm";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user's cart
$sql = "SELECT cartID FROM CART WHERE ref_userID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $ref_userID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<h3>🛒 Your cart is empty.</h3><a href='view_products.php'>⬅ Browse Products</a>";
    exit();
}

$cartID = $result->fetch_assoc()['cartID'];

// Get cart items with product info
$sql = "SELECT p.ProductName, p.Price, c.QuantityOrdered, (p.Price * c.QuantityOrdered) AS SubTotal
        FROM CART_ITEMS c
        JOIN PRODUCT p ON c.ref_productID = p.productID
        WHERE c.ref_cartID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $cartID);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
echo "<h2>🛒 Your Cart</h2>";
echo "<table border='1' cellpadding='5'>
<tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>{$row['ProductName']}</td>
            <td>₱" . number_format($row['Price'], 2) . "</td>
            <td>{$row['QuantityOrdered']}</td>
            <td>₱" . number_format($row['SubTotal'], 2) . "</td>
          </tr>";
    $total += $row['SubTotal'];
}

echo "</table>";
echo "<h3>Total: ₱" . number_format($total, 2) . "</h3>";

$conn->query("UPDATE CART SET Total = $total WHERE cartID = '$cartID'");

echo "<a href='view_products.php'>⬅ Continue Shopping</a>";
$conn->close();
?>
