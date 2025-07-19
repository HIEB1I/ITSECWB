<?php
session_start();
require_once 'db_connect.php';  // Uses session-based user (Admin/Staff only)

// Prevent customers from accessing
if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Staff') {
    die("<h3>❌ Access denied. You are not allowed to upload products.</h3>");
}

// ✅ Begin transaction
$conn->autocommit(FALSE);

try {
    // 🔹 Generate new productID
    $sql = "SELECT productID FROM PRODUCT ORDER BY productID DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $lastID = $result->fetch_assoc()['productID'];
        $num = (int)substr($lastID, 1) + 1;
        $productID = 'P' . str_pad($num, 5, '0', STR_PAD_LEFT);
    } else {
        $productID = 'P00001';
    }

    // 🔹 Get form data
    $productName = $_POST['ProductName'];
    $size = $_POST['Size'];
    $category = $_POST['Category'];
    $description = $_POST['Description'];
    $quantity = $_POST['QuantityAvail'];
    $price = $_POST['Price'];

    // 🔹 Handle image
    $image = $_FILES['Image']['tmp_name'];
    $imageData = file_get_contents($image);

    // 🔹 Prepare and execute insert
    $stmt = $conn->prepare("INSERT INTO PRODUCT 
        (productID, ProductName, Size, Category, Description, QuantityAvail, Price, Image) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception("Statement preparation failed: " . $conn->error);
    }

    $stmt->bind_param("sssssids", 
        $productID, $productName, $size, $category, $description, $quantity, $price, $imageData);

    if (!$stmt->execute()) {
        throw new Exception("Execution failed: " . $stmt->error);
    }

    // ✅ Commit if all successful
    $conn->commit();
    header("Location: view_products.php");
    exit();

} catch (Exception $e) {
    // ❌ Rollback on any failure
    $conn->rollback();
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}
?>
