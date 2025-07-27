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

// If the user is not logged in, we treat them as a guest
$is_guest = !isset($_SESSION['userID']);

// Query to fetch products by category
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
  <title>Guest Home ‒ KALYE WEST</title>
  <style>
    :root {
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

    /* Modal styles */
    #login-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(0, 0, 0, 0.4);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }
    #login-modal .modal-content {
      background: white;
      padding: 30px 20px;
      width: 300px;
      border-radius: 8px;
      text-align: center;
    }
    #login-modal button {
      padding: 6px 12px;
      background: #0C619B;
      color: white;
      border: none;
      cursor: pointer;
      margin: 10px;
    }
    #login-modal button.cancel {
      background: #ccc;
    }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<header>
  <div class="logo">
    <a href="GUEST_Homepage.php"><img src="../Logos/KW Logo.png" alt="KALYE WEST"></a>
  </div>
  <div class="utils">
    <select>
      <option selected>Php</option>
      <option>USD</option>
      <option>KRW</option>
    </select>
    <a href="javascript:void(0)" onclick="promptLogin()"><i class="fa-regular fa-user"></i></a>
    <a href="javascript:void(0)" onclick="promptLogin()"><i class="fa-solid fa-bag-shopping"></i></a>
  </div>
</header>

<!-- CATEGORY SECTIONS -->

<!-- TEES -->
<section>
  <div class="section-title">
    <span class="bold">KALYE WEST</span> <span class="regular">TEES</span>
  </div>

  <div class="grid">
    <?php foreach ($tees as $product) { ?>
    <div class="card" onclick="promptLogin()">
      <?= getImageTag($product['Image'], $product['ProductName']) ?>
      <div class="title"><?= $product['ProductName'] ?></div>
      <div class="brand"><?= $product['Category'] ?></div>
      <div class="price">₱<?= number_format($product['Price'], 2) ?></div>
    </div>
    <?php } ?>
  </div>

  <div class="view-all"><a href="javascript:void(0)" onclick="promptLogin()">View All</a></div>
</section>

<!-- BOTTOMS -->
<section>
  <div class="section-title">
    <span class="bold">KALYE WEST</span> <span class="regular">BOTTOMS</span>
  </div>

  <div class="grid">
    <?php foreach ($bottoms as $product) { ?>
    <div class="card" onclick="promptLogin()">
      <?= getImageTag($product['Image'], $product['ProductName']) ?>
      <div class="title"><?= $product['ProductName'] ?></div>
      <div class="brand"><?= $product['Category'] ?></div>
      <div class="price">₱<?= number_format($product['Price'], 2) ?></div>
    </div>
    <?php } ?>
  </div>

  <div class="view-all"><a href="javascript:void(0)" onclick="promptLogin()">View All</a></div>
</section>

<!-- LAYERING -->
<section>
  <div class="section-title">
    <span class="bold">KALYE WEST</span> <span class="regular">LAYERING</span>
  </div>

  <div class="grid">
    <?php foreach ($layering as $product) { ?>
    <div class="card" onclick="promptLogin()">
      <?= getImageTag($product['Image'], $product['ProductName']) ?>
      <div class="title"><?= $product['ProductName'] ?></div>
      <div class="brand"><?= $product['Category'] ?></div>
      <div class="price">₱<?= number_format($product['Price'], 2) ?></div>
    </div>
    <?php } ?>
  </div>

  <div class="view-all"><a href="javascript:void(0)" onclick="promptLogin()">View All</a></div>
</section>

<!-- Footer -->
<footer>
  <div class="social">
    <a href="https://www.facebook.com/mnlaofficial/" target="_blank"><i class="fa-brands fa-facebook"></i></a>
    <a href="https://www.instagram.com/mnlaofficial/" target="_blank"><i class="fa-brands fa-instagram"></i></a>
    <a href="https://www.tiktok.com/@mnlaofficial" target="_blank"><i class="fa-brands fa-tiktok"></i></a>
  </div>
  <div class="copyright">2025, KALYE WEST</div>
</footer>

<!-- LOGIN MODAL -->
<div id="login-modal">
  <div class="modal-content">
    <p>Please log in to continue.</p>
    <button onclick="goToLogin()">Login</button>
    <button class="cancel" onclick="closeModal()">Cancel</button>
  </div>
</div>

<script>
  function promptLogin() {
    document.getElementById("login-modal").style.display = "flex";
  }

  function closeModal() {
    document.getElementById("login-modal").style.display = "none";
  }

  function goToLogin() {
    window.location.href = "login.php"; // Redirect to login page
  }
</script>

</body>
</html>
