<?php
session_start();

// Database credentials
$host = "localhost";
$user = "root";
$password = "";
$dbname = "dbadm";

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// If the user is not logged in, redirect to login page
if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

// Query to fetch products for all categories
//$sql = "SELECT * FROM PRODUCT";
//$result = $conn->query($sql);

// Fetch products by category
$tees = [];
$bottoms = [];
$layering = [];

function getImageTag($imageData, $alt = '', $class = 'product-img') {
    if ($imageData) {
        $imgData = base64_encode($imageData);
        return "<img src='data:image/png;base64,{$imgData}' alt='" . htmlspecialchars($alt) . "' class='" . htmlspecialchars($class) . "'>";
    }
    return '';
}

$sql_tees = "SELECT * FROM PRODUCT WHERE Category = 'TEES'";
$result_tees = $conn->query($sql_tees);
if ($result_tees && $result_tees->num_rows > 0) {
    while ($row = $result_tees->fetch_assoc()) {
        $tees[] = $row;
    }
}

$sql_bottoms = "SELECT * FROM PRODUCT WHERE Category = 'BOTTOMS'";
$result_bottoms = $conn->query($sql_bottoms);
if ($result_bottoms && $result_bottoms->num_rows > 0) {
    while ($row = $result_bottoms->fetch_assoc()) {
        $bottoms[] = $row;
    }
}

$sql_layering = "SELECT * FROM PRODUCT WHERE Category = 'LAYERING'";
$result_layering = $conn->query($sql_layering);
if ($result_layering && $result_layering->num_rows > 0) {
    while ($row = $result_layering->fetch_assoc()) {
        $layering[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Home ‒ KALYE WEST</title>
  <style>
    :root {
      --nav-height: 110px;
      --brand-black: #000;
      --light-gray: #f5f5f7;
      --mid-gray: #dadada;
      font-family: Arial, Helvetica, sans-serif;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #fff; color: var(--brand-black); }
    a { color: inherit; text-decoration: none; }
    header {
      height: var(--nav-height);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 24px;
      border-bottom: 1px solid var(--mid-gray);
    }
    .logo {
      flex: 1;
      display: flex;
      justify-content: center;
      margin: 20px 0;
    }
    .logo img {
      width: 150px;
      height: auto;
      margin-left: 100px;
    }
    .utils { display: flex; align-items: center; gap: 20px; }
    .utils select, .utils i { font-size: 14px; cursor: pointer; }

    nav {
      display: flex;
      justify-content: center;
      gap: 50px;
      border-bottom: 1px solid var(--mid-gray);
    }
    nav a {
      padding: 18px 0;
      font-size: 13px;
      letter-spacing: 0.5px;
    }
    .section-title {
    text-align: center;
    padding: 40px 0 10px;
    font-size: 20px;
    }

    .section-title .bold {
    font-weight: bold;
    }

    .section-title .regular {
    font-weight: normal;
    }

    .grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 8px; 
    padding: 15px 24px;
    }
    .card {
    width: 200px; 
    text-align: center;
    }
    .card img {
    width: 90%;
    max-width: 200px;
    margin: 0 auto;
    object-fit: contain;
    border: 1px solid var(--mid-gray);
    display: block;
    }
    .card .title {
      margin-top: 10px;
      font-size: 12px;
      text-transform: uppercase;
    }
    .card .brand {
      font-size: 10px;
      color: #666;
      margin-top: 3px;
    }
    .card .price {
      font-size: 12px;
      margin-top: 4px;
    }
    .view-all {
      display: flex;
      justify-content: center;
      margin-top: 20px;
      margin-bottom: 40px;
    }
    .view-all a {
      padding: 6px 18px;
      background: #0C619B;
      color: white;
      font-size: 11px;
      text-transform: uppercase;
    }
    footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 40px;
      border-top: 1px solid #ccc;
      font-size: 14px;
    }
    footer .social i {
      margin: 0 10px;
      font-size: 18px;
      cursor: pointer;
    }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<header>
  <div class="logo">
    <a href="HOME_Homepage.php"><img src="../Logos/KW Logo.png" alt="KALYE WEST"></a>
  </div>
  <div class="utils">
    <select>
      <option selected>Php</option>
      <option>USD</option>
      <option>KRW</option>
    </select>
    <a href="../PROFILE_User.html"><i class="fa-regular fa-user"></i></a>
    <a href="../CART_ViewCart.html"><i class="fa-solid fa-bag-shopping"></i></a>
  </div>
</header>

<nav>
  <a href="CATEGORY_Tees.php">TEES</a>
  <a href="CATEGORY_Bottoms.php">BOTTOMS</a>
  <a href="CATEGORY_Layering.php">LAYERING</a>
</nav>

<!-- CATEGORY SECTIONS -->

<!-- TEES -->
<section>
  <div class="section-title">
    <span class="bold">KALYE WEST</span> <span class="regular">TEES</span>
  </div>

  <div class="grid">
    <?php if (!empty($tees)) {
        foreach ($tees as $product) { ?>
        <a href="Products.php?id=<?= $product['productID'] ?>" class="card">
          <?= getImageTag($product['Image'], $product['ProductName']) ?>
          <div class="title"><?= $product['ProductName'] ?></div>
          <div class="brand"><?= $product['Category'] ?></div>
          <div class="price">₱<?= number_format($product['Price'], 2) ?></div>
        </a>
    <?php }} else { ?>
        <p>No products found.</p>
    <?php } ?>
  </div>

  <div class="view-all"><a href="CATEGORY_Tees.php">View All</a></div>
</section>

<!-- BOTTOMS -->
<section>
  <div class="section-title">
    <span class="bold">KALYE WEST</span> <span class="regular">BOTTOMS</span>
  </div>

  <div class="grid">
    <?php if (!empty($bottoms)) {
        foreach ($bottoms as $product) { ?>
        <a href="Products.phpl?id=<?= $product['productID'] ?>" class="card">
          <?= getImageTag($product['Image'], $product['ProductName']) ?>
          <div class="title"><?= $product['ProductName'] ?></div>
          <div class="brand"><?= $product['Category'] ?></div>
          <div class="price">₱<?= number_format($product['Price'], 2) ?></div>
        </a>
    <?php }} else { ?>
        <p>No products found.</p>
    <?php } ?>
  </div>

  <div class="view-all"><a href="CATEGORY_Bottoms.php">View All</a></div>
</section>

<!-- LAYERING -->
<section>
  <div class="section-title">
    <span class="bold">KALYE WEST</span> <span class="regular">LAYERING</span>
  </div>

  <div class="grid">
    <?php if (!empty($layering)) {
        foreach ($layering as $product) { ?>
        <a href="Products.php?id=<?= $product['productID'] ?>" class="card">
          <?= getImageTag($product['Image'], $product['ProductName']) ?>
          <div class="title"><?= $product['ProductName'] ?></div>
          <div class="brand"><?= $product['Category'] ?></div>
          <div class="price">₱<?= number_format($product['Price'], 2) ?></div>
        </a>
    <?php }} else { ?>
        <p>No products found.</p>
    <?php } ?>
  </div>

  <div class="view-all"><a href="CATEGORY_Layering.php">View All</a></div>
</section>

<footer>
  <div class="social">
    <a href="https://www.facebook.com/mnlaofficial/" target="_blank"><i class="fa-brands fa-facebook"></i></a>
    <a href="https://www.instagram.com/mnlaofficial/" target="_blank"><i class="fa-brands fa-instagram"></i></a>
    <a href="https://www.tiktok.com/@mnlaofficial" target="_blank"><i class="fa-brands fa-tiktok"></i></a>
  </div>
  <div class="copyright">2025, KALYE WEST</div>
</footer>

</body>
</html>
