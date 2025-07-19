<?php
session_start();
require_once 'db_connect.php';

// 🔒 Access control: Only Admin/Staff
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    exit("❌ Access denied. Only Admin and Staff can edit products.");
}

// 📦 Load product data for editing
if (!isset($_GET['productID'])) {
    exit("❌ Product ID is missing.");
}

$productID = $_GET['productID'];

$stmt = $conn->prepare("SELECT * FROM PRODUCT WHERE productID = ?");
$stmt->bind_param("s", $productID);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    exit("❌ Product not found.");
}

$product = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Edit Product</title>
</head>
<body>
<h2>Edit Product - <?= htmlspecialchars($product['ProductName']) ?></h2>

<form action="update_product.php" method="post" enctype="multipart/form-data">
  <input type="hidden" name="productID" value="<?= $product['productID'] ?>">

  Product Name: <input type="text" name="ProductName" value="<?= htmlspecialchars($product['ProductName']) ?>" required><br><br>

  Size:
  <select name="Size" required>
    <?php
    $sizes = ['Extra-Small', 'Small', 'Medium', 'Large', 'Extra-Large'];
    foreach ($sizes as $size) {
        $selected = ($product['Size'] === $size) ? "selected" : "";
        echo "<option value='$size' $selected>$size</option>";
    }
    ?>
  </select><br><br>

  Category: <input type="text" name="Category" value="<?= htmlspecialchars($product['Category']) ?>" required><br><br>

  Description:<br>
  <textarea name="Description" rows="4" cols="40"><?= htmlspecialchars($product['Description']) ?></textarea><br><br>

  Quantity: <input type="number" name="QuantityAvail" value="<?= $product['QuantityAvail'] ?>" min="0" required><br><br>

  Price (₱): <input type="number" name="Price" value="<?= $product['Price'] ?>" step="0.01" required><br><br>

  Change Image (optional): <input type="file" name="Image"><br><br>

  <input type="submit" value="✅ Save Changes">
</form>

<br>
<a href="view_products.php">⬅ Back to Products</a>
</body>
</html>
