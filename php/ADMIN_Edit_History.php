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
  <title>Edit History — KALYE WEST</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { font-family: Arial, sans-serif; background: #fff; color: #000; height: 100%; }
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
    .sidebar h4 { margin: 30px 0 10px; font-size: 14px; font-weight: bold; }
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
      overflow-y: auto;
    }
    h2 { margin-bottom: 32px; font-size: 32px; font-weight: normal; }
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
    th { background: #f3f3f3; }
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
      <a href="view_products.php">Browse</a>
      <h4>ACCOUNT</h4>
      <a href="ADMIN_ManageUsers.php">Manage Users</a>
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
      <h2>Edit History</h2>
      <table>
        <thead>
          <tr>
            <th>Product ID</th>
            <th>Old Name</th>
            <th>New Name</th>
            <th>Old Price</th>
            <th>New Price</th>
            <th>Old Quantity</th>
            <th>New Quantity</th>
            <th>Old Size</th>
            <th>New Size</th>
            <th>Old Category</th>
            <th>New Category</th>
            <th>Old Description</th>
            <th>New Description</th>
            <th>Time Changed</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $result = $conn->query("SELECT * FROM PRODUCT_EDIT_AUDIT ORDER BY Time_Change DESC");
        if ($result && $result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
            echo "<tr>
              <td>{$row['productID']}</td>
              <td>{$row['Old_ProductName']}</td>
              <td>{$row['New_ProductName']}</td>
              <td>" . number_format($row['Old_Price'], 2) . "</td>
              <td>" . number_format($row['New_Price'], 2) . "</td>
              <td>{$row['Old_Quantity']}</td>
              <td>{$row['New_Quantity']}</td>
              <td>{$row['Old_Size']}</td>
              <td>{$row['New_Size']}</td>
              <td>{$row['Old_Category']}</td>
              <td>{$row['New_Category']}</td>
              <td>{$row['Old_Description']}</td>
              <td>{$row['New_Description']}</td>
              <td>{$row['Time_Change']}</td>
            </tr>";
          }
        } else {
          echo "<tr><td colspan='14'>No edit history found.</td></tr>";
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
