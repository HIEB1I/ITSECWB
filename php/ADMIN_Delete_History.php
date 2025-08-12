<?php
// Admin + Staff page access
require_once 'auth_check.php';
requireRole(['Admin', 'Staff']); // admins + staff allowed
require_once 'db_connect.php';


$result = $conn->query("SELECT * FROM PRODUCT_DELETE_AUDIT ORDER BY Time_Deleted DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Deleted Products History - KALYE WEST</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; font-family: Arial, sans-serif; background: #fff; color: #000; }
    body { display: flex; }

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

    .sidebar a.active { text-decoration: underline; text-underline-offset: 4px; }
    .sidebar a:hover { opacity: 0.7; }
    .sidebar .logout { margin-top: 40px; display: flex; align-items: center; gap: 8px; }

    .main-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    header {
      padding: 20px;
      text-align: center;
      border-bottom: 1px solid #ccc;
    }

    .logo img { width: 150px; }

    main {
      padding: 40px;
      flex: 1;
    }

    h2 {
      margin-bottom: 20px;
      font-size: 32px;
      font-weight: normal;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    th, td {
      border: 1px solid #ccc;
      padding: 10px;
      font-size: 14px;
      text-align: left;
    }

    th { background: #f3f3f3; }
    img { width: 50px; }

    footer {
      padding: 20px 40px;
      border-top: 1px solid #ccc;
      font-size: 14px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .social i {
      color: black;
      margin: 0 10px;
      font-size: 18px;
    }
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
    <a href="../html/login.html">Log Out</a>
  </div>
</div>

<div class="main-content">
  <header>
    <div class="logo">
      <img src="../Logos/KW Logo.png" alt="KALYE WEST">
    </div>
  </header>

  <main>
    <h2>Deleted Products Audit Log</h2>
    <table>
      <thead>
        <tr>
          <th>Product ID</th>
          <th>Name</th>
          <th>Size</th>
          <th>Category</th>
          <th>Description</th>
          <th>Qty</th>
          <th>Price</th>
          <th>Image</th>
          <th>Deleted At</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $row['productID'] ?></td>
            <td><?= $row['ProductName'] ?></td>
            <td><?= $row['Size'] ?></td>
            <td><?= $row['Category'] ?></td>
            <td><?= nl2br($row['Description']) ?></td>
            <td><?= $row['QuantityAvail'] ?></td>
            <td>₱<?= number_format($row['Price'], 2) ?></td>
            <td>
              <?php if ($row['Image']): ?>
                <img src="data:image/jpeg;base64,<?= base64_encode($row['Image']) ?>" />
              <?php else: ?>—<?php endif; ?>
            </td>
            <td><?= $row['Time_Deleted'] ?></td>
          </tr>
        <?php endwhile; ?>
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
