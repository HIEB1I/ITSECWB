<?php
session_start();

// Allow only Admins
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'Admin') {
    exit("Access denied.");
}

require_once 'db_connect.php';

$cartID = $_GET['cartID'] ?? null;
if (!$cartID) {
    exit("❌ No cart ID provided.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = (int)$_POST['quantity'];
    $currency = $_POST['currency'];
    $payment = $_POST['payment'];
    $status = $_POST['status'];
    $ship_date = $_POST['ship_date'] ?: null;

   
    $stmtTotal = $conn->prepare("
        SELECT SUM(P.Price * CI.QuantityOrdered) AS baseTotal
        FROM CART_ITEMS CI
        JOIN PRODUCT P ON CI.ref_productID = P.productID
        WHERE CI.ref_cartID = ?
    ");
    $stmtTotal->bind_param("s", $cartID);
    $stmtTotal->execute();
    $resTotal = $stmtTotal->get_result();
    $baseTotal = $resTotal->fetch_assoc()['baseTotal'];
    $stmtTotal->close();

    $conversion = [
        'PHP' => 1,
        'USD' => 0.018,  // e.g., 1 PHP = 0.018 USD
        'WON' => 24.3    // e.g., 1 PHP = 24.3 KRW
    ];

    $convertedTotal = $baseTotal * $conversion[$currency];


    $stmt = $conn->prepare("UPDATE CART 
        SET Total = ?, Currency = ?, MOP = ?, Status = ?, Ship_By_Date = ?
        WHERE cartID = ?");
    $stmt->bind_param("dsssss", $convertedTotal, $currency, $payment, $status, $ship_date, $cartID);

    if (!$stmt->execute()) {
        $error = "❌ Failed to update order.";
    } else {
        header("Location: ADMIN_Orders.php");
        exit();
    }
}

// Load current order
$stmt = $conn->prepare("
    SELECT C.cartID, C.ref_userID, C.Total, C.Currency, C.MOP as PaymentMethod,
           C.Status, C.Order_Date, C.Ship_By_Date,
           CI.QuantityOrdered as Qty,
           P.ProductName, P.Size
    FROM CART C
    JOIN CART_ITEMS CI ON C.cartID = CI.ref_cartID
    JOIN PRODUCT P ON CI.ref_productID = P.productID
    WHERE C.cartID = ?
    LIMIT 1
");
$stmt->bind_param("s", $cartID);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    exit("❌ Order not found.");
}
$r = $res->fetch_assoc(); // ✅ Now $r is defined and safe
?>
<!DOCTYPE html>
<html lang="en">
<!-- KEEP all head and styles exactly as given -->
<head>
  <meta charset="UTF-8">
  <title>Admin Edit Order ‒ KALYE WEST</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style> * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html, body {
      height: 100%;
      font-family: Arial, sans-serif;
      background: #fff;
      color: #000;
    }

    body {
      display: flex;
      height: 100vh;
      overflow: hidden;
    }

    .sidebar {
      width: 220px;
      background: #111;
      color: white;
      padding: 40px 20px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .sidebar h4 {
      margin: 30px 0 10px;
      font-weight: bold;
      font-size: 14px;
    }

    .sidebar a {
      color: white;
      text-decoration: none;
      margin: 8px 0;
      display: block;
      font-size: 14px;
      transition: 0.2s;
    }

    .sidebar a.active {
      text-decoration: underline;
      text-underline-offset: 4px;
    }

    .sidebar a:hover {
      opacity: 0.7;
    }

    .sidebar .logout {
      margin-top: 40px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .main-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      height: 100vh;
      overflow: hidden;
    }

    header {
      padding: 20px;
      text-align: center;
      border-bottom: 1px solid #ccc;
      flex-shrink: 0;
    }

    .logo img {
      width: 150px;
    }

    main {
      padding: 20px 40px;
      flex: 1;
      overflow-y: auto;
    }

    h2 {
      margin-bottom: 30px;
      font-size: 32px;
      font-weight: normal;
    }

    form label {
      display: block;
      margin: 15px 0 5px;
      font-weight: bold;
      font-size: 14px;
    }

    form input[type="text"],
    form input[type="number"],
    form textarea,
    form select {
      width: 100%;
      padding: 8px;
      border: 1px solid #000;
      font-size: 14px;
    }

    form textarea {
      height: 100px;
      resize: none;
    }

    .form-row {
      display: flex;
      gap: 20px;
      margin-top: 10px;
    }

    .form-row > div {
      flex: 1;
    }

    .upload-box {
      border: 1px dashed #333;
      padding: 40px;
      text-align: center;
      margin-top: 10px;
      cursor: pointer;
    }

    .upload-box i {
      font-size: 24px;
      margin-bottom: 10px;
    }

    .upload-box small {
      display: block;
      margin-top: 5px;
      color: #555;
      font-size: 12px;
    }

    .save-btn {
      background-color: #c0e8c2;
      color: black;
      padding: 10px 20px;
      border: none;
      margin-top: 20px;
      font-size: 14px;
      cursor: pointer;
    }

    footer {
      padding: 20px 40px;
      border-top: 1px solid #ccc;
      font-size: 14px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-shrink: 0;
    }

    .social i {
      margin: 0 10px;
      font-size: 18px;
      color: black;
    }</style>
</head>
<body>

  <div class="sidebar">
    <div>
      <h4>DASHBOARD</h4>
      <a href="ADMIN_Dashboard.html">Product</a>
      <a href="ADMIN_Orders.html"   class="active">Order</a>
      <a href="ADMIN_Browse.html">Browse</a>
      <h4>ACCOUNT</h4>
      <a href="ADMIN_ManageUsers.html">Manage Users</a>
    </div>
    <div class="logout">
      <i class="fa-solid fa-right-from-bracket"></i>
      <a href="Login.html">Log Out</a>
    </div>

</div>
<div class="main-content">
  <header>
    <div class="logo">
      <img src="../Logos/KW Logo.png" alt="KALYE WEST">
    </div>
  </header>
  <main>
    <form method="POST">
      <div style="background: #f5f5f5; padding: 15px; margin-bottom: 20px; font-weight: bold; text-align: center;">EDIT ORDER</div>

      <p><strong>ORDER ID:</strong> <?= htmlspecialchars($r['cartID']) ?></p>
      <p><strong>USER ID:</strong> <?= htmlspecialchars($r['ref_userID']) ?></p>

      <h4 style="margin-top: 20px; margin-bottom: 10px; font-size: 40px;">PRODUCT DETAILS</h4>
      <p><strong>PRODUCT NAME:</strong> <?= htmlspecialchars($r['ProductName']) ?></p>
      <p><strong>SIZE:</strong> <?= htmlspecialchars($r['Size']) ?></p>

      <label for="quantity">QUANTITY:</label>
      <input type="number" id="quantity" name="quantity" value="<?= htmlspecialchars($r['Qty']) ?>">

      <label for="currency">CURRENCY:</label>
      <select id="currency" name="currency">
        <?php
        foreach (['PHP','USD','WON'] as $c) {
            $sel = ($r['Currency'] === $c) ? 'selected' : '';
            echo "<option value='$c' $sel>$c</option>";
        }
        ?>
      </select>

      <label for="price">PRICE:</label>
      <input type="text" id="price" name="price" value="<?= number_format($r['Total'], 2) ?>" readonly>

      <label for="payment">MODE OF PAYMENT:</label>
      <select id="payment" name="payment">
        <?php
        foreach (['COD', 'GCash', 'Card'] as $pay) {
            $sel = ($r['PaymentMethod'] === $pay) ? 'selected' : '';
            echo "<option value='$pay' $sel>$pay</option>";
        }
        ?>
      </select>

      <label for="status">STATUS:</label>
      <select id="status" name="status">
        <?php
        foreach (['TO SHIP','SHIPPED','DELIVERED'] as $st) {
            $sel = ($r['Status'] === $st) ? 'selected' : '';
            echo "<option value='$st' $sel>$st</option>";
        }
        ?>
      </select>

      <label for="order-date">ORDER DATE:</label>
      <input type="date" id="order-date" name="order_date" value="<?= substr($r['Order_Date'], 0, 10) ?>" readonly>

      <label for="ship-date">SHIP BY DATE:</label>
      <input type="date" id="ship-date" name="ship_date" value="<?= $r['Ship_By_Date'] ? substr($r['Ship_By_Date'], 0, 10) : '' ?>">

      <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

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


  <script> /* KEEP your existing JS if any */ </script>
</body>
</html>
