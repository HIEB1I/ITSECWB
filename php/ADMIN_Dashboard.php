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
  <title>Admin Dashboard ‒ KALYE WEST</title>
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
      justify-content: space-between;
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
      margin-bottom: 40px;
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

    th { background: #f3f3f3; }
    img.product-img { width: 40px; }
    .actions i { margin-right: 10px; cursor: pointer; }
    .actions a i.fa-pen-to-square { color: black; }
    .actions i.fa-trash { color: red; }

    .add-btn {
      padding: 10px 20px;
      border: 1px solid #000;
      background: transparent;
      cursor: pointer;
      font-size: 14px;
      transition: 0.2s;
    }

    .add-btn:hover { background: #000; color: #fff; }

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

     .history-buttons {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }
    .history-buttons a button {
      padding: 10px 20px;
      border: 1px solid #000;
      background: transparent;
      cursor: pointer;
      font-size: 14px;
      transition: 0.2s;
    }
    .history-buttons a button:hover {
      background: #000;
      color: #fff;
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
      <h2>Welcome back, Admin!</h2>
<div class="history-buttons">
      <a href="ADMIN_Delete_History.php"><button>View Delete History</button></a>
      <a href="ADMIN_Edit_History.php"><button>View Edit History</button></a>
    </div>
      <table>
        <thead>
          <tr>
            <th></th>
            <th>PRODUCT NAME</th>
            <th>PRODUCT ID</th>
            <th>CATEGORY</th>
            <th>DESCRIPTION</th>
            <th>SIZE</th>
            <th>QTY</th>
            <th>PRICE</th>
            <th>ACTIONS</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $result = $conn->query("SELECT * FROM PRODUCT");
          if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              $imgData = base64_encode($row['Image']);
              $imgTag = $imgData ? "<img src='data:image/jpeg;base64,{$imgData}' class='product-img'>" : "";
              echo '<tr>
            <td>' . $imgTag . '</td>
            <td>' . $row['ProductName'] . '</td>
            <td>' . $row['productID'] . '</td>
            <td>' . $row['Category'] . '</td>
            <td>' . nl2br($row['Description']) . '</td>
            <td>' . $row['Size'] . '</td>
            <td>' . $row['QuantityAvail'] . '</td>
            <td>₱' . number_format($row['Price'], 2) . '</td>
            <td class="actions">
                <a href="ADMIN_EditProduct.php?productID=' . $row['productID'] . '"><i class="fa-solid fa-pen-to-square"></i></a>
               <i class="fa-solid fa-trash" onclick="showModal(\'' . $row['productID'] . '\')"></i>
            </td>
            </tr>';
            }
          } else {
            echo "<tr><td colspan='9'>No products found.</td></tr>";
          }
          $conn->close();
          ?>
        </tbody>
      </table>

      <a href="ADMIN_AddProduct.php"><button class="add-btn">+ ADD PRODUCT</button></a>
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

  <!-- Delete Confirmation Modal -->
<div class="modal" id="deleteModal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center;">
  <div class="modal-content" style="background: white; padding: 30px; text-align: center; border-radius: 10px; width: 300px;">
    <p style="margin-bottom: 20px;">Are you sure you want to delete this product?</p>
    <button onclick="hideModal()" style="background: #ccc; padding: 8px 16px; margin: 0 10px; border: none; cursor: pointer;">Cancel</button>
    <button onclick="confirmDelete()" style="background: red; color: white; padding: 8px 16px; border: none; cursor: pointer;">Delete</button>
  </div>
</div>


</body>
</html>


<script>
  let productIdToDelete = null;

  function showModal(productID) {
    productIdToDelete = productID;
    document.getElementById('deleteModal').style.display = 'flex';
  }

  function hideModal() {
    productIdToDelete = null;
    document.getElementById('deleteModal').style.display = 'none';
  }

  function confirmDelete() {
    if (productIdToDelete) {
      window.location.href = "ADMIN_DeleteProduct.php?productID=" + encodeURIComponent(productIdToDelete);
    }
  }
</script>