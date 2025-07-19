<?php
session_start();
require_once 'db_connect.php';

// 🔒 Access control
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    exit("❌ Access denied.");
}

// ✅ Input
$productID = $_POST['productID'];
$name = $_POST['ProductName'];
$size = $_POST['Size'];
$category = $_POST['Category'];
$desc = $_POST['Description'];
$qty = (int)$_POST['QuantityAvail'];
$price = (float)$_POST['Price'];

// Check if image was uploaded
if (isset($_FILES['Image']) && $_FILES['Image']['size'] > 0) {
    $imgData = file_get_contents($_FILES['Image']['tmp_name']);
    $stmt = $conn->prepare("UPDATE PRODUCT 
        SET ProductName = ?, Size = ?, Category = ?, Description = ?, QuantityAvail = ?, Price = ?, Image = ?
        WHERE productID = ?");
    $stmt->bind_param("ssssddbs", $name, $size, $category, $desc, $qty, $price, $imgData, $productID);
} else {
    $stmt = $conn->prepare("UPDATE PRODUCT 
        SET ProductName = ?, Size = ?, Category = ?, Description = ?, QuantityAvail = ?, Price = ?
        WHERE productID = ?");
    $stmt->bind_param("ssssdds", $name, $size, $category, $desc, $qty, $price, $productID);
}

// 🔁 Execute
if ($stmt->execute()) {
    echo "<h3>✅ Product updated successfully.</h3>";
} else {
    echo "<h3>❌ Update failed: " . $stmt->error . "</h3>";
}

$stmt->close();
$conn->close();

echo "<br><a href='view_products.php'>⬅ Back to Products</a>";
?>
