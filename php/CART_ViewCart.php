<?php
session_start();
require_once 'db_connect.php';
if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}
$userID = $_SESSION['userID'];
// Get active cart
$stmt = $conn->prepare("SELECT cartID FROM CART WHERE ref_userID = ? AND Purchased = FALSE");
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "<h3>Your cart is empty.</h3><a href='view_products.php'>⬅ Browse Products</a>";
    exit();
}
$cartID = $result->fetch_assoc()['cartID'];
$stmt->close();
// Get cart items
$sql = "SELECT CI.cartItemsID, P.ProductName, P.Price, CI.QuantityOrdered, (P.Price * CI.QuantityOrdered) AS SubTotal, P.Image, P.Size FROM CART_ITEMS CI JOIN PRODUCT P ON CI.ref_productID = P.productID WHERE CI.ref_cartID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $cartID);
$stmt->execute();
$result = $stmt->get_result();
$total = 0;
$cartItems = [];
while ($row = $result->fetch_assoc()) {
    $cartItems[] = $row;
    $total += $row['SubTotal'];
}
$stmt->close();
// Update total in CART table
$update = $conn->prepare("UPDATE CART SET Total = ? WHERE cartID = ?");
$update->bind_param("ds", $total, $cartID);
$update->execute();
$update->close();
// Get existing Currency & MOP to preselect
$getCart = $conn->prepare("SELECT Currency, MOP FROM CART WHERE cartID = ?");
$getCart->bind_param("s", $cartID);
$getCart->execute();
$cartInfo = $getCart->get_result()->fetch_assoc();
$getCart->close();
$selectedCurrency = $cartInfo['Currency'] ?? '';
$selectedMOP = $cartInfo['MOP'] ?? '';
function getImageTag($imageData, $alt = '', $class = 'product-img') {
    if ($imageData) {
        $imgData = base64_encode($imageData);
        return "<img src='data:image/png;base64,{$imgData}' alt='" . htmlspecialchars($alt) . "' class='" . htmlspecialchars($class) . "' style='width:80px;'>";
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Cart ‒ KALYE WEST</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root { --nav-height: 110px; --brand-black: #000; --light-gray: #f5f5f7; --mid-gray: #dadada; font-family: Arial, Helvetica, sans-serif; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #fff; color: var(--brand-black); }
    a { color: inherit; text-decoration: none; }
    header { height: var(--nav-height); display: flex; align-items: center; justify-content: space-between; padding: 0 24px; border-bottom: 1px solid var(--mid-gray); }
    .logo { flex: 1; display: flex; justify-content: center; margin: 20px 0; }
    .logo img { width: 150px; height: auto; margin-left: 100px; }
    .utils { display: flex; align-items: center; gap: 20px; }
    .utils select, .utils i { font-size: 14px; cursor: pointer; }
    nav { display: flex; justify-content: center; gap: 50px; margin-top: -1px; border-bottom: 1px solid var(--mid-gray); }
    nav a { padding: 18px 0; font-size: 13px; letter-spacing: 0.5px; }
    nav a.active { font-weight: bold; border-bottom: 2px solid var(--brand-black); }
    table { width:100%; border-collapse: collapse; margin-top: 40px; }
    th, td { padding: 16px; border-bottom: 1px solid #ccc; vertical-align: middle; }
    td img { width: 80px; margin-right: 10px; }
    .product-info { display: flex; align-items: center; gap: 10px; }
    .quantity-controls { display: flex; align-items: center; gap: 5px; }
    .quantity-controls button { padding: 4px 10px; font-size: 14px; cursor: pointer; }
    .total { text-align: right; font-size: 18px; margin-top: 20px; }
    .checkout { text-align: right; margin-top: 20px; }
    .checkout button { background: #000; color: white; padding: 12px 24px; border: none; cursor: pointer; }
    footer { display: flex; justify-content: space-between; align-items: center; padding: 20px 40px; border-top: 1px solid #ccc; font-size: 14px; margin-top: 50px; }
    footer .social i { margin: 0 10px; font-size: 18px; cursor: pointer; }
    footer .copyright { font-size: 14px; }
    .cart-container { max-width: 850px; margin: 40px auto 0; padding: 0 20px; }
    .cart-summary { display: flex; flex-direction: column; align-items: flex-end; margin-top: 20px; gap: 10px; }
    .total { font-size: 18px; width: 250px; text-align: right; }
    .checkout button { background: #000; color: white; padding: 12px; border: none; cursor: pointer; width: 250px; text-align: center; }
    html, body { height: 100%; }
    .page-wrapper { display: flex; flex-direction: column; min-height: 100vh; }
    .content { flex: 1; }
  </style>
</head>
<body>
  <div class="page-wrapper">
  <div class="content">
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
      <a href="PROFILE_User.php"><i class="fa-regular fa-user"></i></a>
      <a href="CART_ViewCart.php"><i class="fa-solid fa-bag-shopping"></i></a>
    </div>
  </header>
  <nav>
    <a href="CATEGORY_Tees.php"  class="active">TEES</a>
    <a href="CATEGORY_Bottoms.php">BOTTOMS</a>
    <a href="CATEGORY_Layering.php">LAYERING</a>
  </nav>
  <div class="cart-container">
    <h2 style="margin: 40px 0 20px;">Your Cart</h2>
    <table>
      <thead>
        <tr>
          <th>Product</th>
          <th>Quantity</th>
          <th>Price</th>
          <th>Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($cartItems)) { ?>
          <tr><td colspan="5" style="text-align:center; padding: 40px; font-size: 16px; color: #666;">Your cart is empty</td></tr>
        <?php } else {
          foreach ($cartItems as $row) { ?>
          <tr>
            <td>
              <div class="product-info">
                <?= getImageTag($row['Image'], $row['ProductName']) ?>
                <div>
                  <strong><?= htmlspecialchars($row['ProductName']) ?></strong><br>
                  Size: <?= htmlspecialchars($row['Size']) ?>
                </div>
              </div>
            </td>
            <td>
              <form action='update_cart_item.php' method='post' style='display:inline;'>
                <input type='number' name='Quantity' min='1' value='<?= htmlspecialchars($row['QuantityOrdered']) ?>' required style='width: 60px;'>
                <input type='hidden' name='cartItemsID' value='<?= htmlspecialchars($row['cartItemsID']) ?>'>
                <button type='submit' name='update'>Update</button>
                <button type='submit' name='delete' onclick="return confirm('Remove this item?')">🗑️</button>
              </form>
            </td>
            <td>₱<?= number_format($row['Price'], 2) ?></td>
            <td>₱<?= number_format($row['SubTotal'], 2) ?></td>
            <td></td>
          </tr>
        <?php }} ?>
      </tbody>
    </table>
    <div class="cart-summary">
      <div class="total">
        ESTIMATED TOTAL: <span id="estimatedTotal">₱<?= number_format($total ?? 0, 2) ?></span>
      </div>
      <div class="checkout">
        <form action="checkout.php" method="post">
          <label for='currency'><strong>Currency:</strong></label>
          <select name='currency' id='currency' required>
            <option value=''>-- Select --</option>
            <option value='PHP' <?= ($selectedCurrency === 'PHP' ? 'selected' : '') ?>>PHP</option>
            <option value='USD' <?= ($selectedCurrency === 'USD' ? 'selected' : '') ?>>USD</option>
            <option value='WON' <?= ($selectedCurrency === 'WON' ? 'selected' : '') ?>>WON</option>
          </select>
          <br><br>
          <label for='payment_method'><strong>Mode of Payment:</strong></label>
          <select name='payment_method' id='payment_method' required>
            <option value=''>-- Select --</option>
            <option value='COD' <?= ($selectedMOP === 'COD' ? 'selected' : '') ?>>COD</option>
            <option value='GCash' <?= ($selectedMOP === 'GCash' ? 'selected' : '') ?>>GCASH</option>
            <option value='Card' <?= ($selectedMOP === 'Card' ? 'selected' : '') ?>>CARD</option>
          </select>
          <br><br>
          <input type='hidden' name='cartID' value='<?= htmlspecialchars($cartID) ?>'>
          <button type="submit">Check out</button>
        </form>
      </div>
    </div>
    </div>
  </div>
  <footer>
    <div class="social">
      <a href="https://www.facebook.com/mnlaofficial/" target="_blank"><i class="fa-brands fa-facebook"></i></a>
      <a href="https://www.instagram.com/mnlaofficial/?hl=en" target="_blank"><i class="fa-brands fa-instagram"></i></a>
      <a href="https://www.tiktok.com/@mnlaofficial?_t=ZS-8xh9NMarft4&_r=1" target="_blank"><i class="fa-brands fa-tiktok"></i></a>
    </div>
    <div class="copyright">2025, KALYE WEST</div>
  </footer>
</body>
</html>
