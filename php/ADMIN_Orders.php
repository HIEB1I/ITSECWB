<?php
session_start();
if (!isset($_SESSION['userID']) || $_SESSION['role'] == 'Customer') {
  exit("Access denied.");
}

require_once 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Orders ‒ KALYE WEST</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
* {
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
      justify-content: space-between;
      min-height: 100vh;
    }

    header {
      padding: 20px;
      text-align: center;
      border-bottom: 1px solid #ccc;
    }

    .logo img {
      width: 150px;
    }

    main {
      padding: 40px;
      flex: 1;
    }

    h2 {
      margin-bottom: 32px;
      font-size: 40px;
      font-weight: normal;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }

    th, td {
      padding: 12px;
      border-bottom: 1px solid #ddd;
      text-align: left;
      font-size: 14px;
    }

    th {
      background: #f3f3f3;
    }

    img.product-img {
      width: 40px;
    }

    .actions i {
      margin-right: 10px;
      cursor: pointer;
    }

    .social i {
    color: black;
    }

    .actions a i.fa-pen-to-square {
    color: black;
    }
    .actions i.fa-trash {
    color: red;
    }

    .add-btn {
      padding: 10px 20px;
      border: 1px solid #000;
      background: transparent;
      cursor: pointer;
      font-size: 14px;
      transition: 0.2s;
    }

    .add-btn:hover {
      background: #000;
      color: #fff;
    }

    footer {
      padding: 20px 40px;
      border-top: 1px solid #ccc;
      font-size: 14px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .social i {
      margin: 0 10px;
      font-size: 18px;
    }

    .modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      display: none;
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background: white;
      padding: 30px;
      text-align: center;
      border-radius: 10px;
      width: 300px;
    }

    .modal-content button {
      padding: 8px 16px;
      margin: 10px;
      border: none;
      cursor: pointer;
    }

    .btn-cancel {
      background: #ccc;
    }

    .btn-delete {
      background: red;
      color: white;
    }
  </style>
</head>
<body>
<div class="sidebar">
  <div>
    <h4>DASHBOARD</h4>
    <a href="ADMIN_Dashboard.php">Product</a>
    <a href="ADMIN_Orders.php" class="active">Order</a>
    <a href="view_products.php">Browse</a>
    <h4>ACCOUNT</h4>
    <a href="ADMIN_ManageUsers.php">Manage Users</a>
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
    <a href="ADMIN_OrdersHistory.php"><button class="add-btn">View Order History</button></a>
    <h2>ORDERS</h2>
    <table>
      <thead>
        <tr>
          <th></th>
          <th>ORDER ID</th>
          <th>USER ID</th>
          <th>PRODUCT DETAILS</th>
          <th>ORDER TOTAL</th>
          <th>MOP</th>
          <th>STATUS</th>
          <th>ORDER DATE</th>
          <th>SHIP BY DATE</th>
          <th>ACTIONS</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $query = "SELECT * FROM CART ORDER BY Order_Date DESC";
        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
            $cartID = $row['cartID'];
            $userID = $row['ref_userID'];

            // Get userID properly from USERS table if missing
            $userResult = $conn->prepare("SELECT userID FROM USERS WHERE userID = ?");
            $userResult->bind_param("s", $userID);
            $userResult->execute();
            $userData = $userResult->get_result()->fetch_assoc();
            $userID = $userData['userID'] ?? 'N/A';
            $userResult->close();

            // Get product details
            $details = "";
            $productStmt = $conn->prepare("SELECT P.ProductName, CI.QuantityOrdered, P.Size 
              FROM CART_ITEMS CI 
              JOIN PRODUCT P ON CI.ref_productID = P.productID 
              WHERE CI.ref_cartID = ?");
            $productStmt->bind_param("s", $cartID);
            $productStmt->execute();
            $products = $productStmt->get_result();
            while ($p = $products->fetch_assoc()) {
              $details .= "<div style='margin-bottom: 8px;'><strong>{$p['ProductName']}</strong><br>Qty: {$p['QuantityOrdered']}<br>Size: {$p['Size']}</div>";
            }
            $productStmt->close();

            $symbol = match($row['Currency']) {
              'PHP' => '₱',
              'USD' => '$',
              'WON' => '₩',
              default => '',
            };

            echo "<tr>
              <td><i class='fa-solid fa-box'></i></td>
              <td>{$cartID}</td>
              <td>{$userID}</td>
              <td>{$details}</td>
              <td>{$symbol}" . number_format($row['Total'], 2) . "</td>
              <td>{$row['MOP']}</td>
              <td>{$row['Status']}</td>
              <td>{$row['Order_Date']}</td>
              <td>" . ($row['Ship_By_Date'] ?? '—') . "</td>
              <td class='actions'>
                <a href='ADMIN_EditOrder.php?cartID={$cartID}'><i class='fa-solid fa-pen-to-square'></i></a>
              </td>
            </tr>";
          }
        } else {
          echo "<tr><td colspan='10'>No orders found.</td></tr>";
        }
        $conn->close();
        ?>
      </tbody>
    </table>
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
</body>
</html>
