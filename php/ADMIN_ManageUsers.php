<?php
// Admin page access
require_once 'auth_check.php';
requireRole(['Admin']); // only admins allowed
require_once 'db_connect.php';


$stmt = $conn->prepare("SELECT userID, FirstName, LastName, Email, Password, Address,  Role, Created_At FROM USERS");
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users ‒ KALYE WEST</title>
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

    .sidebar a.active {
      text-decoration: underline;
      text-underline-offset: 4px;
    }

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

    .actions i {
      margin-right: 10px;
      cursor: pointer;
    }

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
      color: black;
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

    .btn-cancel { background: #ccc; }
    .btn-delete { background: red; color: white; }
  </style>
</head>
<body>

<div class="sidebar">
  <div>
    <h4>DASHBOARD</h4>
    <a href="ADMIN_Dashboard.php">Product</a>
    <a href="ADMIN_Orders.php">Order</a>
   <a href="HOME_Homepage.php">Browse</a>
    <h4>ACCOUNT</h4>
    <a href="ADMIN_ManageUsers.php" class="active">Manage Users</a>
    <h4>LOGS</h4>
    <a href="ADMIN_SecurityLogs.php">Security Logs</a>
  </div>
  <div class="logout">
    <i class="fa-solid fa-right-from-bracket"></i>
    <a href="login.php">Log Out</a>
  </div>
</div>

<div class="main-content">
  <header>
    <div class="logo">
      <img src="../Logos/KW Logo.png" alt="KALYE WEST">
    </div>
  </header>

  <main>
    <h2>MANAGE USERS</h2>

    <table>
      <thead>
        <tr>
          <th>USER ID</th>
          <th>FULL NAME</th>
          <th>EMAIL</th>
          <th>PASSWORD</th>
          <th>ROLE</th>
          <th>ADDRESS</th> 
          <th>JOINED DATE</th>
          <th>ACTIONS</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
          <tr>
            <td><?= htmlspecialchars($user['userID']) ?></td>
            <td><?= htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) ?></td>
            <td><?= htmlspecialchars($user['Email']) ?></td>
            <td><?= str_repeat('*', 8) ?></td> 
            <td><?= htmlspecialchars($user['Role']) ?></td>
            <td><?= htmlspecialchars($user['Address'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($user['Created_At']) ?></td>
            <td class="actions">
              <a href="ADMIN_EditUser.php?userID=<?= $user['userID'] ?>"><i class="fa-solid fa-pen-to-square"></i></a>
              <i class="fa-solid fa-trash" onclick="deleteUser('<?= $user['userID'] ?>')"></i>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <a href="ADMIN_AddUser.php"><button class="add-btn">+ ADD USER</button></a>
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

<div class="modal" id="deleteModal">
  <div class="modal-content">
    <p>Are you sure you want to delete this user?</p>
    <button class="btn-cancel" onclick="hideModal()">Cancel</button>
    <button class="btn-delete" onclick="confirmDelete()">Delete</button>
  </div>
</div>

<script>
let userIdToDelete = null;

function showModal() {
  document.getElementById('deleteModal').style.display = 'flex';
}

function hideModal() {
  document.getElementById('deleteModal').style.display = 'none';
}

function deleteUser(id) {
  userIdToDelete = id;
  showModal();
}

function confirmDelete() {
  window.location.href = "ADMIN_DeleteUser.php?userID=" + userIdToDelete;
}
</script>


</body>
</html>
