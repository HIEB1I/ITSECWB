<?php
session_start();

if (!isset($_SESSION['userID'])) {
    echo "Access denied. Please <a href='../html/login.html'>login</a>.";
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

// Generate new productID
$sql = "SELECT productID FROM PRODUCT ORDER BY productID DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $lastID = $result->fetch_assoc()['productID'];
    $num = (int)substr($lastID, 1);
    $num++;
    $productID = 'P' . str_pad($num, 5, '0', STR_PAD_LEFT);
} else {
    $productID = 'P00001';
}

// Get form data
$productName = $_POST['ProductName'];
$size = $_POST['Size'];
$category = $_POST['Category'];
$description = $_POST['Description'];
$quantity = $_POST['QuantityAvail'];
$price = $_POST['Price'];

// Handle image
$image = $_FILES['Image']['tmp_name'];
$imageData = file_get_contents($image);

// Insert into DB
$stmt = $conn->prepare("INSERT INTO PRODUCT 
    (productID, ProductName, Size, Category, Description, QuantityAvail, Price, Image) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("sssssids", 
    $productID, $productName, $size, $category, $description, $quantity, $price, $imageData);
    
if ($stmt->execute()) {
   header("Location: view_products.php");
exit();
} else {
    echo "<h3>❌ Error: " . $stmt->error . "</h3>";
}

$stmt->close();
$conn->close();
?>
