<?php
function getImageTag($imageData, $alt = '', $class = 'product-img') {
    if ($imageData) {
        $imgData = base64_encode($imageData);
        return "<img src='data:image/png;base64,{$imgData}' alt='" . htmlspecialchars($alt) . "' class='" . htmlspecialchars($class) . "'>";
    }
    return '';
}
session_start();
require_once 'db_connect.php';

// Get productID from query string
if (!isset($_GET['id'])) {
    die('Product not specified.');
}
$productID = $_GET['id'];

// Fetch product from database
$stmt = $conn->prepare('SELECT * FROM PRODUCT WHERE productID = ? LIMIT 1');
$stmt->bind_param('s', $productID);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
if (!$product) {
    die('Product not found.');
}

// Check initial stock for default size
$initialSize = 'EXTRA SMALL'; // Default size
$stmt = $conn->prepare("CALL check_product_stock(?, ?, @available, @product_name)");
$stmt->bind_param("ss", $productID, $initialSize);
$stmt->execute();
$stmt->close();
$conn->next_result();

// Get the output parameters
$result = $conn->query("SELECT @available as stock, @product_name as name");
$stockInfo = $result->fetch_assoc();
$currentStock = $stockInfo['stock'] ?? 0;

// Get all sizes and their stocks for the same product name
$stmt = $conn->prepare('SELECT Size, QuantityAvail FROM PRODUCT WHERE ProductName = ?');
$stmt->bind_param('s', $product['ProductName']);
$stmt->execute();
$sizeStocks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Create a size-to-stock mapping
$stockBySize = [];
foreach ($sizeStocks as $item) {
    $stockBySize[$item['Size']] = $item['QuantityAvail'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Product Detail ‒ KALYE WEST</title>
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
    header { height: var(--nav-height); display: flex; align-items: center; justify-content: space-between; padding: 0 24px; border-bottom: 1px solid var(--mid-gray); }
    .logo { flex: 1; display: flex; justify-content: center; margin: 20px 0; }
    .logo img { width: 150px; height: auto; margin-left: 100px; }
    .utils { display: flex; align-items: center; gap: 20px; }
    .utils select, .utils i { font-size: 14px; cursor: pointer; }
    nav { display: flex; justify-content: center; gap: 50px; margin-top: -1px; border-bottom: 1px solid var(--mid-gray); }
    nav a { padding: 18px 0; font-size: 13px; letter-spacing: .5px; }
    nav a.active { font-weight: bold; border-bottom: 2px solid var(--brand-black); }
    .product-section { display: flex; padding: 60px; gap: 60px; align-items: flex-start; }
    .product-section img { width: 600px; object-fit: contain; border: 1px solid var(--mid-gray); }
    .details { max-width: 600px; }
    .details h2 { font-size: 20px; margin-bottom: 8px; text-transform: uppercase; }
    .details .price { font-size: 18px; margin-bottom: 16px; }
    .sizes { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
    .sizes button { padding: 8px 12px; font-size: 12px; border: 1px solid #aaa; background: #fff; cursor: pointer; }
    .sizes button.selected { background: #000; color: #fff; }
    .quantity { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
    .quantity button { padding: 6px 10px; cursor: pointer; }
    .add-to-cart { background: #004b84; color: white; border: none; padding: 10px 20px; font-size: 14px; cursor: pointer; margin-bottom: 30px; }
    .features { margin-top: 20px; line-height: 1.8; font-size: 14px; }
    footer { display: flex; justify-content: space-between; align-items: center; padding: 20px 40px; border-top: 1px solid #ccc; font-size: 14px; }
    footer .social i { margin: 0 10px; font-size: 18px; cursor: pointer; }
    footer .copyright { font-size: 14px; }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
   <header>
    <div class="logo">
      <a href="../php/HOME_Homepage.php"><img src="../Logos/KW Logo.png" alt="KALYE WEST"></a>
    </div>
    <div class="utils">
      <select>
        <option selected>Php</option>
        <option>USD</option>
        <option>KRW</option>
      </select>
      <a href="../php/PROFILE_User.php"><i class="fa-regular fa-user"></i></a>
      <a href="../php/CART_ViewCart.php"><i class="fa-solid fa-bag-shopping"></i></a>
    </div>
  </header>

  <nav>
    <a href="CATEGORY_Tees.php">TEES</a>
    <a href="CATEGORY_Bottoms.php">BOTTOMS</a>
    <a href="CATEGORY_Layering.php">LAYERING</a>
  </nav>

  <section class="product-section">
    <?= getImageTag($product['Image'], $product['ProductName']) ?>
    <div class="details">
      <h2><?= htmlspecialchars($product['ProductName']) ?></h2>
      <div class="price">₱<?= number_format($product['Price'], 2) ?></div>
      <div class="sizes" id="sizes">
        <?php foreach ($stockBySize as $size => $stock): ?>
            <button 
                data-size="<?= htmlspecialchars($size) ?>" 
                data-stock="<?= htmlspecialchars($stock) ?>" 
                onclick="selectSize(this)" 
                <?= $size === 'EXTRA SMALL' ? 'class="selected"' : '' ?>>
                <?= htmlspecialchars($size) ?>
            </button>
        <?php endforeach; ?>
      </div>
      <div class="quantity">
        <button type="button" onclick="changeQuantity(-1)">-</button>
        <span id="quantityValue">1</span>
        <button type="button" onclick="changeQuantity(1)">+</button>
        <span class="stock-info" id="stockInfo">
            Stock: <?= htmlspecialchars($currentStock) ?>
        </span>
        <input type="hidden" name="quantity" id="quantity" value="1">
      </div>
      <form id="addToCartForm" action="add_to_cart.php" method="post" style="margin-top: 20px;">
        <input type="hidden" name="productID" value="<?= htmlspecialchars($product['productID']) ?>">
        <input type="hidden" id="selectedSize" name="size" value="EXTRA SMALL">
        <label for="quantity">Quantity:</label>
        <input type="number" id="quantity" name="Quantity" value="1" min="1" required style="width: 60px;">
        <button type="submit" class="add-to-cart">Add to cart</button>
      </form>
      <div id="cartPopup" style="display:none;position:fixed;top:30px;left:50%;transform:translateX(-50%);background:#0C619B;color:#fff;padding:16px 32px;border-radius:8px;z-index:9999;font-size:18px;">Product added to cart!</div>
      <div class="features">
        <ul id="featureList">
          <li><?= htmlspecialchars($product['Description']) ?></li>
        </ul>
      </div>
    </div>
  </section>

 <footer>
    <div class="social">
      <a href="https://www.facebook.com/mnlaofficial/" target="_blank"><i class="fa-brands fa-facebook"></i></a>
      <a href="https://www.instagram.com/mnlaofficial/?hl=en" target="_blank"><i class="fa-brands fa-instagram"></i></a>
      <a href="https://www.tiktok.com/@mnlaofficial?_t=ZS-8xh9NMarft4&_r=1" target="_blank"><i class="fa-brands fa-tiktok"></i></a>
    </div>
    <div class="copyright">2025, KALYE WEST</div>
  </footer>

  <script>
function selectSize(button) {
    const allButtons = document.querySelectorAll('#sizes button');
    allButtons.forEach(btn => btn.classList.remove('selected'));
    button.classList.add('selected');
    
    // Update hidden input with selected size
    const selectedSize = button.getAttribute('data-size');
    const stockLevel = button.getAttribute('data-stock');
    document.getElementById('selectedSize').value = selectedSize;
    
    // Update stock display
    document.getElementById('stockInfo').textContent = `Stock: ${stockLevel}`;
    
    // Reset quantity to 1
    document.getElementById('quantityValue').textContent = '1';
    document.getElementById('quantity').value = '1';
}

function changeQuantity(delta) {
    const quantityElem = document.getElementById('quantityValue');
    const quantityInput = document.getElementById('quantity');
    const selectedButton = document.querySelector('#sizes button.selected');
    const maxStock = parseInt(selectedButton.getAttribute('data-stock'));
    let current = parseInt(quantityElem.innerText);
    
    // Calculate new quantity
    let newQuantity = current + delta;
    
    // Ensure quantity is between 1 and available stock
    if (newQuantity >= 1 && newQuantity <= maxStock) {
        quantityElem.innerText = newQuantity;
        quantityInput.value = newQuantity;
    }
}
  </script>
</body>
</html>