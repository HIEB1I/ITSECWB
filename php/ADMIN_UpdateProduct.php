<?php
// Admin + Staff page access
require_once 'auth_check.php';
requireRole(['Admin', 'Staff']); // admins + staff allowed
require_once 'db_connect.php';
require_once 'validation.php';



// Input
$productID = $_POST['productID'];
$name = $_POST['ProductName'];
$size = $_POST['Size'];
$category = $_POST['Category'];
$desc = $_POST['Description'];
$qty = (int)$_POST['QuantityAvail'];
$price = (float)$_POST['Price'];

// DATA VALIDATION: validation checks & compile
$errors = [];

  if (!validateString($name, 3, 100)) {
    $errors[] = "Product name must be between 3 and 100 characters.";
  }
  if (!validateString($description, 10, 500)) {
    $errors[] = "Invalid description: must be between 10 and 500 characters.";
  }
  if (!validateNumber($quantity, 1, 1000)) {
    $errors[] = "Invalid quantity: must be between 1 and 1000.";
  }
  if (!validateNumber($price, 0, 100000)) {
    $errors[] = "Invalid price: must be between 0 and 100000.";
  } 

  if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: ADMIN_EditProduct.php");
    exit;
  }

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
    $stmt->send_long_data(6, $imgData); // Index is 0-based, so 6 = 7th param 

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

echo "<br><a href='ADMIN_Dashboard.php'></a>";
?>
