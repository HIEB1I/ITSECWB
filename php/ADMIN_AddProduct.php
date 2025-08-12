<?php
// Admin + Staff page access
require_once 'auth_check.php';
requireRole(['Admin', 'Staff']); // admins + staff allowed
require_once 'db_connect.php';
require_once 'validation.php';
require_once 'security_logger.php';

$logger = new SecurityLogger($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = $_POST['name'];
  $category = $_POST['category'];
  $description = $_POST['description'];
  $size = $_POST['size'];
  $quantity = $_POST['quantity'];
  $price = $_POST['price'];

  // DATA VALIDATION: validation checks & compile
  $errors = [];

  if (!validateString($name, 3, 100)) {
    $errors[] = "Product name must be between 3 and 100 characters.";
    $logger->logInputValidationFailure("ProductName", "Length between 3 and 100", $name);
  }
  if (!validateString($description, 10, 500)) {
    $errors[] = "Invalid description: must be between 10 and 500 characters.";
    $logger->logInputValidationFailure("Description", "Length between 10 and 500", $description);
  }
  if (!validateNumber($quantity, 1, 1000)) {
    $errors[] = "Invalid quantity: must be between 1 and 1000.";
    $logger->logInputValidationFailure("QuantityAvail", "Between 1 and 1000", $quantity);
  }
  if (!validateNumber($price, 0, 100000)) {
    $errors[] = "Invalid price: must be between 0 and 100000.";
    $logger->logInputValidationFailure("Price", "Between 0 and 100000", $price);
  } 

  if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    // Log a general validation failure event
    $logger->logEvent(
      'INPUT_VALIDATION_FAILURE',
      "Add Product validation failed. Errors: " . implode("; ", $errors),
      $_SESSION['user_id'] ?? null,
      $_SESSION['role'] ?? null
    );
    header("Location: ADMIN_AddProduct.php");
    exit;
  }

  $result = $conn->query("SELECT productID FROM PRODUCT ORDER BY productID DESC LIMIT 1");
  if ($result && $result->num_rows > 0) {
    $lastID = $result->fetch_assoc()['productID'];
    $num = (int)substr($lastID, 1) + 1;
    $newID = 'P' . str_pad($num, 5, '0', STR_PAD_LEFT);
  } else {
    $newID = 'P00001';
  }

  $imageData = null;
  if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $imageData = file_get_contents($_FILES['image']['tmp_name']);
  }

  $stmt = $conn->prepare("INSERT INTO PRODUCT (productID, ProductName, Category, Description, Size, QuantityAvail, Price, Image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("sssssiis", $newID, $name, $category, $description, $size, $quantity, $price, $imageData);


  if ($stmt->execute()) {
    $logger->logEvent(
      'APPLICATION_SUCCESS',
      "Product added successfully: {$newID} - {$name}",
      $_SESSION['user_id'] ?? null,
      $_SESSION['role'] ?? null
    );
    header("Location: ADMIN_Dashboard.php");
    exit;
  } else {
    $error = "Failed to add product.";
    $logger->logApplicationError(
      "Failed to insert new product: {$newID} - {$name}",
      $stmt->error
    );
  }
}
$imageBase64 = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Add Product ‒ KALYE WEST</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; font-family: Arial, sans-serif; background: #fff; color: #000; }
    body { display: flex; height: 100vh; overflow: hidden; }
    .sidebar {
      width: 220px; background: #111; color: white; padding: 40px 20px;
      display: flex; flex-direction: column; justify-content: space-between;
    }
    .sidebar h4 { margin: 30px 0 10px; font-weight: bold; font-size: 14px; }
    .sidebar a { color: white; text-decoration: none; margin: 8px 0; display: block; font-size: 14px; transition: 0.2s; }
    .sidebar a.active { text-decoration: underline; text-underline-offset: 4px; }
    .sidebar a:hover { opacity: 0.7; }
    .sidebar .logout { margin-top: 40px; display: flex; align-items: center; gap: 8px; }
    .main-content { flex: 1; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
    header { padding: 20px; text-align: center; border-bottom: 1px solid #ccc; flex-shrink: 0; }
    .logo img { width: 150px; }
    main { padding: 20px 40px; flex: 1; overflow-y: auto; }
    h2 { margin-bottom: 30px; font-size: 32px; font-weight: normal; }
    form label { display: block; margin: 15px 0 5px; font-weight: bold; font-size: 14px; }
    form input[type="text"], form input[type="number"], form textarea, form select {
      width: 100%; padding: 8px; border: 1px solid #000; font-size: 14px;
    }
    form textarea { height: 100px; resize: none; }
    .form-row { display: flex; gap: 20px; margin-top: 10px; }
    .form-row > div { flex: 1; }
    .upload-box {
      border: 1px dashed #333; padding: 40px; text-align: center; margin-top: 10px;
    }
    .upload-box i { font-size: 24px; margin-bottom: 10px; }
    .upload-box small { display: block; margin-top: 5px; color: #555; font-size: 12px; }
    .save-btn {
      background-color: #c0e8c2; color: black; padding: 10px 20px;
      border: none; margin-top: 20px; font-size: 14px; cursor: pointer;
    }
    footer {
      padding: 20px 40px; border-top: 1px solid #ccc; font-size: 14px;
      display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;
    }
    .social i { margin: 0 10px; font-size: 18px; color: black; }
  </style>
</head>
<body>
  <div class="sidebar">
    <div>
      <h4>DASHBOARD</h4>
      <a href="ADMIN_Dashboard.php" class="active">Product</a>
      <a href="ADMIN_Orders.php">Order</a>
      <a href="HOME_Homepage.php">Browse</a>
      <h4>ACCOUNT</h4>
      <a href="ADMIN_ManageUsers.php">Manage Users</a>
      <h4>LOGS</h4>
      <a href="ADMIN_SecurityLogs.php">Security Logs</a>
    </div>
    <div class="logout">
      <i class="fa-solid fa-right-from-bracket"></i>
      <a href="../php/login.php">Log Out</a>
    </div>
  </div>

  <div class="main-content">
    <header>
      <div class="logo">
        <img src="../Logos/KW Logo.png" alt="KALYE WEST">
      </div>
    </header>

    <main>
      <form method="POST" enctype="multipart/form-data">
        <div style="background: #f5f5f5; padding: 15px; margin-bottom: 20px; font-weight: bold; text-align: center;">ADD PRODUCT</div>

        <!-- DATA VALIDATION: display compiled errors -->
        <?php if (!empty($_SESSION['errors'])): ?>
          <ul style="color:red;">
            <?php foreach ($_SESSION['errors'] as $error) echo "<li>$error</li>"; ?>
          </ul>
            <?php unset($_SESSION['errors']); ?>
        <?php endif; ?>

        <label for="name">PRODUCT NAME:</label>
        <input type="text" id="name" name="name" required>

        <label for="category">CATEGORY:</label>
        <select id="category" name="category" required>
          <option value="">Select Category</option>
          <option value="Tees">Tees</option>
          <option value="Bottoms">Bottoms</option>
          <option value="Layering">Layering</option>
        </select>

        <label for="description">DESCRIPTION:</label>
        <textarea id="description" name="description" required></textarea>

        <div class="form-row">
          <div>
            <label for="size">SIZE:</label>
            <select id="size" name="size" required>
              <option value="">Select Size</option>
              <option value="Extra-Small">Extra-Small</option>
              <option value="Small">Small</option>
              <option value="Medium">Medium</option>
              <option value="Large">Large</option>
              <option value="Extra-Large">Extra-Large</option>
            </select>
          </div>
          <div>
            <label for="quantity">QUANTITY:</label>
            <input type="number" id="quantity" name="quantity" required>
          </div>
          <div>
            <label for="price">PRICE:</label>
            <input type="number" step="0.01" id="price" name="price" placeholder="0.00" required>
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
        <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
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
          const preview = document.getElementById("preview");
          const icon = document.getElementById("upload-icon");
          preview.src = e.target.result;
          preview.style.display = "block";
          icon.style.display = "none";
          document.getElementById("upload-text").textContent = "Image selected!";
        };
        reader.readAsDataURL(file);
      }
    });
  </script>
</body>
</html>