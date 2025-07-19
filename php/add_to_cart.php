<?php
session_start();
if (!isset($_SESSION['userID'])) {
    echo "Access denied.";
    exit();
}

$ref_userID = $_SESSION['userID'];
$productID = $_POST['productID'];
$quantity = $_POST['Quantity'];

$host = "localhost";
$user = "root";
$password = "";
$dbname = "dbadm";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user already has a cart
$sql = "SELECT cartID FROM CART WHERE ref_userID = ? AND Purchased = FALSE";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $ref_userID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $cartID = $result->fetch_assoc()['cartID'];
} else {
    // Generate new cart ID
    $sql = "SELECT cartID FROM CART ORDER BY cartID DESC LIMIT 1";
    $res = $conn->query($sql);
    $lastID = $res->num_rows > 0 ? $res->fetch_assoc()['cartID'] : 'C00000';
    $num = (int)substr($lastID, 1) + 1;
    $cartID = 'C' . str_pad($num, 5, '0', STR_PAD_LEFT);

    // Create new cart
    $insertCart = $conn->prepare("INSERT INTO CART (cartID, Total, ref_userID) VALUES (?, 0, ?)");
    $insertCart->bind_param("ss", $cartID, $ref_userID);
    $insertCart->execute();
}

//Add item to CART_ITEMS
$sql = "SELECT cartItemsID FROM CART_ITEMS ORDER BY cartItemsID DESC LIMIT 1";
$res = $conn->query($sql);
$lastCI = $res->num_rows > 0 ? $res->fetch_assoc()['cartItemsID'] : 'CI00000';
$num = (int)substr($lastCI, 2) + 1;
$cartItemsID = 'CI' . str_pad($num, 5, '0', STR_PAD_LEFT);

$addItem = $conn->prepare("INSERT INTO CART_ITEMS (cartItemsID, QuantityOrdered, ref_productID, ref_cartID) VALUES (?, ?, ?, ?)");
$addItem->bind_param("ssss", $cartItemsID, $quantity, $productID, $cartID);
$addItem->execute();

echo "<h3>✅ Item added to cart!</h3>";
echo "<a href='view_products.php'>⬅ Back to Products</a> | <a href='view_cart.php'>🛒 View Cart</a>";

$conn->close();
?>
