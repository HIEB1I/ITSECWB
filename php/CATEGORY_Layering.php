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

// Fetch products for the 'LAYERING' category
$sql = "SELECT * FROM PRODUCT WHERE Category = 'LAYERING'";
$result = $conn->query($sql);

// Check if there are products for this category
if ($result->num_rows > 0) {
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
} else {
    $products = [];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Layering ‒ KALYE WEST</title>
  <style>
    :root{--nav-height:110px;--brand-black:#000;--light-gray:#f5f5f7;--mid-gray:#dadada;font-family:Arial,Helvetica,sans-serif;}*{box-sizing:border-box;margin:0;padding:0;}body{background:#fff;color:var(--brand-black);}a{color:inherit;text-decoration:none;}header{height:var(--nav-height);display:flex;align-items:center;justify-content:space-between;padding:0 24px;border-bottom:1px solid var(--mid-gray);}
   .logo {
      flex: 1;
      display: flex;
      justify-content: center;
      margin: 20px 0;
    }
    .logo img {
      width: 150px;
      height: auto;
      transition: opacity 0.2s ease;
      margin-left: 100px; 
    }

    .utils{display:flex;align-items:center;gap:20px;}
    .utils select,.utils i{font-size:14px;cursor:pointer;}
    nav{display:flex;justify-content:center;gap:50px;margin-top:-1px;border-bottom:1px solid var(--mid-gray);}
    nav a{padding:18px 0;font-size:13px;letter-spacing:.5px;}
    nav a.active{font-weight:bold;border-bottom:2px solid var(--brand-black);}
    .hero{background:var(--light-gray);padding:40px 0;text-align:center;}
    .hero h1{font-size:50px;}
    .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; padding: 40px 24px; }
    .card{text-align:center;}
    .card img{width:100%;object-fit:contain;border:1px solid var(--mid-gray);}
    .card .title{margin-top:10px;font-size:12px;text-transform:uppercase;}
    .card .brand{font-size:10px;color:#666;margin-top:3px;}
    .card .price{font-size:12px;margin-top:4px;}
    footer{display:flex;justify-content:space-between;align-items:center;padding:20px 40px;border-top:1px solid #ccc;font-size:14px;}
    footer .social i{margin:0 10px;font-size:18px;cursor:pointer;}
    footer .copyright{font-size:14px;}
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
    <a href="PROFILE_User.html"><i class="fa-regular fa-user"></i></a>
    <a href="CART_ViewCart.html"><i class="fa-solid fa-bag-shopping"></i></a>
  </div>
</header>

<!-- CATEGORY NAV -->
<nav>
  <a href="CATEGORY_Tees.php">TEES</a>
  <a href="CATEGORY_Bottoms.php">BOTTOMS</a>
  <a href="CATEGORY_Layering.php" class="active">LAYERING</a>
</nav>

<!-- HERO TITLE -->
<section class="hero">
  <h1><strong>KALYE WEST</strong> LAYERING</h1>
</section>

<!-- PRODUCT GRID -->
<section class="grid">
  <?php if (!empty($products)) {
      foreach ($products as $product) { ?>
      <a href="Products.php?id=<?= $product['productID'] ?>" class="card">
        <img src="CategoryProducts/<?= $product['Image'] ?>" alt="<?= $product['ProductName'] ?>">
        <div class="title"><?= $product['ProductName'] ?></div>
        <div class="brand"><?= $product['Brand'] ?></div>
        <div class="price">₱<?= number_format($product['Price'], 2) ?></div>
      </a>
  <?php }} else { ?>
      <p>No products found.</p>
  <?php } ?>
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
