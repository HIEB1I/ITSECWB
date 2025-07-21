<?php
session_start();
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'Admin') {
  exit("Access denied.");
}
require_once 'db_connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order History - KALYE WEST</title>
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

    /* Sidebar */
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

    /* Main content */
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

    /* Make social icons black */
    .social i {
    color: black;
    }

    /* Make the edit icon black */
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

    /* Delete Modal */
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
  </style>
</head>
<body>
<div class="sidebar">
  <div>
    <h4>DASHBOARD</h4>
    <a href="ADMIN_Dashboard.php">Product</a>
    <a href="ADMIN_Orders.php">Order</a>
    <a href="view_products.php">Browse</a>
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
      <img src="../Logos/KW Logo.png" alt="KALYE WEST">
    </div>
  </header>

  <main>
    <h2>ORDER HISTORY</h2>
    <table>
      <thead>
        <tr>
          <th>Audit ID</th>
          <th>Cart ID</th>
          <th>User ID</th>
          <th>Products</th>
          <th>Total</th>
          <th>MOP</th>
          <th>Status</th>
          <th>Order Date</th>
          <th>Ship By</th>
        </tr>
      </thead>
      <tbody>
      <?php
      $query = "SELECT * FROM CART_AUDIT ORDER BY Order_Date DESC";
      $result = $conn->query($query);

      if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
          $symbol = match ($row['Currency']) {
            'PHP' => '₱',
            'USD' => '$',
            'WON' => '₩',
            default => ''
          };

          // Fetch product details
          $products = "";
          $stmt = $conn->prepare("SELECT Name, QuantityOrdered, Size FROM CART_ITEMS_AUDIT WHERE ref_cartauditID = ?");
          $stmt->bind_param("i", $row['cartauditID']);
          $stmt->execute();
          $items = $stmt->get_result();
          while ($item = $items->fetch_assoc()) {
            $products .= "<div><strong>{$item['Name']}</strong><br>Qty: {$item['QuantityOrdered']}<br>Size: {$item['Size']}</div>";
          }
          $stmt->close();

          echo "<tr>
                  <td>{$row['cartauditID']}</td>
                  <td>{$row['cartID']}</td>
                  <td>{$row['userID']}</td>
                  <td>$products</td>
                  <td>{$symbol}" . number_format($row['Total'], 2) . "</td>
                  <td>{$row['MOP']}</td>
                  <td>{$row['Status']}</td>
                  <td>{$row['Order_Date']}</td>
                  <td>" . ($row['Ship_By_Date'] ?? '—') . "</td>
                </tr>";
        }
      } else {
        echo "<tr><td colspan='10'>No audit history found.</td></tr>";
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
