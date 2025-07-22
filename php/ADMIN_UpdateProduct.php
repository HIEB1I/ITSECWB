<?php
session_start();
require_once 'db_connect.php';

// Access control
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    exit(" Access denied.");
}

// Input
$productID = $_POST['productID'];
$name = $_POST['ProductName'];
$size = $_POST['Size'];
$category = $_POST['Category'];
$desc = $_POST['Description'];
$qty = (int)$_POST['QuantityAvail'];
$price = (float)$_POST['Price'];

//  If image was uploaded
if (isset($_FILES['Image']) && $_FILES['Image']['size'] > 0) {
    $imgData = file_get_contents($_FILES['Image']['tmp_name']);

    // Define all variables separately and properly
    $imagePlaceholder = null;  // This is the one that will be sent long data into
    $stmt = $conn->prepare("UPDATE PRODUCT 
        SET ProductName = ?, Size = ?, Category = ?, Description = ?, QuantityAvail = ?, Price = ?, Image = ?
        WHERE productID = ?");
    
    // Now bind all vars including the placeholder
    $stmt->bind_param("ssssddss", $name, $size, $category, $desc, $qty, $price, $imagePlaceholder, $productID);

    // Send the binary data to placeholder
    $stmt->send_long_data(6, $imgData); // Index is 0-based, so 6 = 7th param (Image)

} else {
    $stmt = $conn->prepare("UPDATE PRODUCT 
        SET ProductName = ?, Size = ?, Category = ?, Description = ?, QuantityAvail = ?, Price = ?
        WHERE productID = ?");
    $stmt->bind_param("ssssdds", $name, $size, $category, $desc, $qty, $price, $productID);
}

// Execute
if ($stmt->execute()) {
    echo "<h3> Product updated successfully.</h3>";
} else {
    echo "<h3>Update failed: " . $stmt->error . "</h3>";
}

$stmt->close();
$conn->close();

echo "<br><a href='ADMIN_Dashboard.php'>⬅ Back to Products</a>";
?>
