<?php
session_start();
if (!isset($_SESSION['userID'])) {
    echo "Access denied. <a href='login.html'>Login</a>";
    exit();
}

$host = "localhost";
$user = "root";
$password = "";
$dbname = "dbadm";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SELECT * FROM PRODUCT");

echo "<h2>All Products</h2>";
echo "<a href='view_cart.php'>🛒 View Cart</a><br><br>";

while ($row = $result->fetch_assoc()) {
    echo "<div style='border:1px solid #ccc; padding:10px; margin:10px'>";
    echo "<strong>{$row['ProductName']}</strong><br>";
    echo "Size: {$row['Size']}<br>";
    echo "Description: {$row['Description']}<br>";
    echo "Available: {$row['QuantityAvail']}<br>";
    echo "Price: ₱" . number_format($row['Price'], 2) . "<br>";

    // Show image
    if (!empty($row['Image'])) {
        $imgData = base64_encode($row['Image']);
        echo "<img src='data:image/jpeg;base64,$imgData' style='max-width:100px'><br>";
    }

    // Add to Cart Form
    echo "
      <form action='add_to_cart.php' method='post'>
        <input type='hidden' name='productID' value='{$row['productID']}'>
        Quantity: <input type='number' name='Quantity' min='1' required>
        <input type='submit' value='Add to Cart'>
      </form>
    ";
    echo "</div>";
}

$conn->close();
?>
