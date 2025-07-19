<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['userID'])) {
  header("Location: login.html");
  exit();
}

require_once 'db_connect.php'; 

$userID = $_SESSION['userID'];
?>

<!DOCTYPE html>
<html>
<head><title>View Products</title></head>
<body>

<h2>Welcome, User ID: <?= $_SESSION['userID'] ?></h2>

<form action="../html/product_upload.html" method="get" style="display:inline;">
  <button type="submit">➕ Upload Product</button>
</form>
<form action="view_cart.php" method="get" style="display:inline;">
  <button type="submit">🛒 View Cart</button>
</form>

<hr>
<h3>All Products:</h3>

<?php
$conn = new mysqli("localhost", "root", "", "dbadm");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// ✅ Logic: If previous cart is purchased, create a new one
$userID = $_SESSION['userID'];

// Check if user has an active cart (Purchased = FALSE)
$checkCart = $conn->prepare("SELECT cartID FROM CART WHERE ref_userID = ? AND Purchased = FALSE");
$checkCart->bind_param("s", $userID);
$checkCart->execute();
$checkCart->store_result();

if ($checkCart->num_rows === 0) {
    // User has no active cart — create a new one
    $getLast = $conn->query("SELECT cartID FROM CART ORDER BY cartID DESC LIMIT 1");
    $lastID = $getLast->num_rows > 0 ? $getLast->fetch_assoc()['cartID'] : 'C00000';
    $nextNum = (int)substr($lastID, 1) + 1;
    $newCartID = 'C' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);

    $insertCart = $conn->prepare("INSERT INTO CART (cartID, Total, Purchased, ref_userID) VALUES (?, 0, FALSE, ?)");
    $insertCart->bind_param("ss", $newCartID, $userID);
    $insertCart->execute();
    $insertCart->close();
}
$checkCart->close();


$result = $conn->query("SELECT * FROM PRODUCT");
if (!$result) die("Query failed: " . $conn->error);
if ($result->num_rows === 0) echo "<p>No products available.</p>";

while ($row = $result->fetch_assoc()) {
  echo "<div style='border:1px solid #ccc; padding:10px; margin:10px'>";
  echo "<strong>{$row['ProductName']}</strong><br>";
  echo "Size: {$row['Size']}<br>";
  echo "Category: {$row['Category']}<br>";
  echo "Description: {$row['Description']}<br>";
  echo "Available: {$row['QuantityAvail']}<br>";
  echo "Price: ₱" . number_format($row['Price'], 2) . "<br>";

  if (!empty($row['Image'])) {
    $imgData = base64_encode($row['Image']);
    echo "<img src='data:image/jpeg;base64,$imgData' style='max-width:100px'><br>";
  }

  echo "<form action='add_to_cart.php' method='post'>";
  echo "<input type='hidden' name='productID' value='{$row['productID']}'>";
  echo "Quantity: <input type='number' name='Quantity' min='1' required>";
  echo "<input type='submit' value='Add to Cart'>";
  echo "</form>";

  echo "</div>";
}
$conn->close();
?>

</body>
</html>
