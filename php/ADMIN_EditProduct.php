<?php
session_start();
if (!isset($_SESSION['userID']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Staff')) {
    exit("Access denied.");
}

require_once 'db_connect.php';

$productID = $_GET['productID'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $size = $_POST['size'];
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];
    $imageData = null;

    if (isset($_FILES['image']) && $_FILES['image']['tmp_name']) {
        $imageData = file_get_contents($_FILES['image']['tmp_name']);
    }

    $query = $imageData 
        ? "UPDATE PRODUCT SET ProductName=?, Category=?, Description=?, Size=?, QuantityAvail=?, Price=?, Image=? WHERE productID=?"
        : "UPDATE PRODUCT SET ProductName=?, Category=?, Description=?, Size=?, QuantityAvail=?, Price=? WHERE productID=?";

    $stmt = $conn->prepare($query);

    if ($imageData) {
        $stmt->bind_param("sssssdss", $name, $category, $description, $size, $quantity, $price, $imageData, $productID);
    } else {
        $stmt->bind_param("ssssdds", $name, $category, $description, $size, $quantity, $price, $productID);
    }

    if ($stmt->execute()) {
        header("Location: ADMIN_Dashboard.php");
        exit();
    } else {
        echo "<script>alert('Failed to update product.');</script>";
    }
}

$stmt = $conn->prepare("SELECT * FROM PRODUCT WHERE productID = ?");
$stmt->bind_param("s", $productID);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    exit("Product not found.");
}
$imageBase64 = $product['Image'] ? 'data:image/jpeg;base64,' . base64_encode($product['Image']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Product ‒ KALYE WEST</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* [KEEPING ORIGINAL STYLES INTACT] */
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; font-family: Arial, sans-serif; background: #fff; color: #000; }
    body { display: flex; height: 100vh; overflow: hidden; }
    .sidebar { width: 220px; background: #111; color: white; padding: 40px 20px; display: flex; flex-direction: column; justify-content: space-between; }
    .sidebar h4 { margin: 30px 0 10px; font-weight: bold; font-size: 14px; }
    .sidebar a { color: white; text-decoration: none; margin: 8px 0; display: block; font-size: 14px; transition: 0.2s; }
    .sidebar a.active { text-decoration: underline; text-underline-offset: 4px; }
    .sidebar a:hover { opacity: 0.7; }
    .logout { margin-top: 40px; display: flex; align-items: center; gap: 8px; }
    .main-content { flex: 1; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
    header { padding: 20px; text-align: center; border-bottom: 1px solid #ccc; flex-shrink: 0; }
    .logo img { width: 150px; }
    main { padding: 20px 40px; flex: 1; overflow-y: auto; }
    h2 { margin-bottom: 30px; font-size: 32px; font-weight: normal; }
    form label { display: block; margin: 15px 0 5px; font-weight: bold; font-size: 14px; }
    input, textarea, select { width: 100%; padding: 8px; border: 1px solid #000; font-size: 14px; }
    textarea { height: 100px; resize: none; }
    .form-row { display: flex; gap: 20px; margin-top: 10px; }
    .form-row > div { flex: 1; }
    .upload-box { border: 1px dashed #333; padding: 40px; text-align: center; margin-top: 10px; cursor: pointer; }
    .upload-box i { font-size: 24px; margin-bottom: 10px; }
    .upload-box small { display: block; margin-top: 5px; color: #555; font-size: 12px; }
    .save-btn { background-color: #c0e8c2; color: black; padding: 10px 20px; border: none; margin-top: 20px; font-size: 14px; cursor: pointer; }
    footer { padding: 20px 40px; border-top: 1px solid #ccc; font-size: 14px; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
    .social i { margin: 0 10px; font-size: 18px; color: black; }
  </style>
</head>
<body>

<div class="sidebar">
  <div>
    <h4>DASHBOARD</h4>
    <a href="ADMIN_Dashboard.php" class="active">Product</a>
    <a href="ADMIN_Orders.html">Order</a>
    <a href="ADMIN_Browse.html">Browse</a>
    <h4>ACCOUNT</h4>
    <a href="ADMIN_ManageUsers.php">Manage Users</a>
  </div>
  <div class="logout">
    <i class="fa-solid fa-right-from-bracket"></i>
    <a href="logout.php">Log Out</a>
  </div>
</div>

<div class="main-content">
  <header>
    <div class="logo">
      <img src="Logos/KW Logo.png" alt="KALYE WEST">
    </div>
  </header>

  <main>
    <form method="POST" enctype="multipart/form-data">
      <div style="background: #f5f5f5; padding: 15px; margin-bottom: 20px; font-weight: bold; text-align: center;">EDIT PRODUCT</div>

      <label>PRODUCT ID:</label>
      <p><?= htmlspecialchars($product['productID']) ?></p>

      <label for="name">PRODUCT NAME:</label>
      <input type="text" name="name" value="<?= htmlspecialchars($product['ProductName']) ?>" required>

      <label for="category">CATEGORY:</label>
      <select name="category" required>
        <option value="">Select Category</option>
        <option value="Tees" <?= $product['Category'] === 'Tees' ? 'selected' : '' ?>>Tees</option>
        <option value="Bottoms" <?= $product['Category'] === 'Bottoms' ? 'selected' : '' ?>>Bottoms</option>
        <option value="Layering" <?= $product['Category'] === 'Layering' ? 'selected' : '' ?>>Layering</option>
      </select>

      <label for="description">DESCRIPTION:</label>
      <textarea name="description" required><?= htmlspecialchars($product['Description']) ?></textarea>

      <div class="form-row">
        <div>
          <label for="size">SIZE:</label>
          <select name="size" required>
            <?php
            $sizes = ["SMALL", "MEDIUM", "LARGE", "EXTRA LARGE", "2X LARGE", "3X LARGE", "4X LARGE"];
            foreach ($sizes as $s) {
              $selected = $product['Size'] === $s ? 'selected' : '';
              echo "<option value=\"$s\" $selected>$s</option>";
            }
            ?>
          </select>
        </div>
        <div>
          <label for="quantity">QUANTITY:</label>
          <input type="number" name="quantity" value="<?= $product['QuantityAvail'] ?>" required>
        </div>
        <div>
          <label for="price">PRICE:</label>
          <input type="number" step="0.01" name="price" value="<?= $product['Price'] ?>" required>
        </div>
      </div>

      <label for="image" class="upload-box">
        <i class="fa-solid fa-image" id="upload-icon" <?= $imageBase64 ? 'style="display:none;"' : '' ?>></i>
        <img id="preview" src="<?= $imageBase64 ?>" style="max-height: 120px; margin: 10px auto; display: <?= $imageBase64 ? 'block' : 'none' ?>;" />
        <div id="upload-text"><?= $imageBase64 ? 'Current image' : 'Drop your image here, or browse' ?></div>
        <small>Supports: JPG & PNG</small>
      </label>
      <input type="file" name="image" id="image" accept="image/png, image/jpeg" style="display:none;">

      <button type="submit" class="save-btn">SAVE</button>
    </form>
  </main>

  <footer>
    <div class="social">
      <a href="#"><i class="fa-brands fa-facebook"></i></a>
      <a href="#"><i class="fa-brands fa-instagram"></i></a>
      <a href="#"><i class="fa-brands fa-tiktok"></i></a>
    </div>
    <div>2025, KALYE WEST</div>
  </footer>
</div>

<script>
document.getElementById("image").addEventListener("change", function () {
  const file = this.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function (e) {
      document.getElementById("preview").src = e.target.result;
      document.getElementById("preview").style.display = "block";
      document.getElementById("upload-icon").style.display = "none";
      document.getElementById("upload-text").textContent = "Image selected!";
    };
    reader.readAsDataURL(file);
  }
});
</script>

</body>
</html>
