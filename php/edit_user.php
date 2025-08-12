<?php
// Any Role
require_once 'auth_check.php';
requireLogin(); // any logged-in role
require_once 'db_connect.php';


$success = "";
$error = "";

// ✅ Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_userID'])) {
    $deleteID = $_POST['delete_userID'];

    // Optional: Prevent deleting your own account
    if ($deleteID === $_SESSION['userID']) {
        $error = "❌ You cannot delete your own admin account.";
    } else {
        $stmt = $conn->prepare("DELETE FROM USERS WHERE userID = ?");
        $stmt->bind_param("s", $deleteID);
        if ($stmt->execute()) {
            $success = "✅ User $deleteID deleted.";
        } else {
            $error = "❌ Failed to delete user: " . $stmt->error;
        }
        $stmt->close();
    }
}

// ✅ Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['userID']) && !isset($_POST['delete_userID'])) {
    $userID = $_POST['userID'];
    $firstName = $_POST['FirstName'];
    $lastName = $_POST['LastName'];
    $email = $_POST['Email'];
    $password = $_POST['Password'];
    $role = $_POST['Role'];

    $stmt = $conn->prepare("UPDATE USERS SET FirstName=?, LastName=?, Email=?, Password=?, Role=? WHERE userID=?");
    $stmt->bind_param("ssssss", $firstName, $lastName, $email, $password, $role, $userID);

    if ($stmt->execute()) {
        $success = "✅ User updated successfully.";
    } else {
        $error = "❌ Update failed: " . $stmt->error;
    }
    $stmt->close();
}

// ✅ Fetch selected user
$editUser = null;
if (isset($_GET['edit'])) {
    $userID = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM USERS WHERE userID = ?");
    $stmt->bind_param("s", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    $editUser = $result->fetch_assoc();
    $stmt->close();
}

// ✅ Get list of users
$users = $conn->query("SELECT userID, FirstName, LastName FROM USERS ORDER BY userID");
?>

<!DOCTYPE html>
<html>
<head><title>Edit Users (Admin Only)</title></head>
<body>

<h2>🛠 Edit Users (Admin Only)</h2>
<a href="view_products.php">⬅ Back to Products</a>
<hr>

<!-- 🧭 Feedback -->
<?php if ($success) echo "<p style='color:green;'>$success</p>"; ?>
<?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>

<!-- 👤 User selector -->
<form method="get" action="edit_user.php">
    <label for="edit">Select User:</label>
    <select name="edit" required>
        <option value="">-- Choose a user --</option>
        <?php while ($row = $users->fetch_assoc()): ?>
            <option value="<?= $row['userID'] ?>" <?= (isset($_GET['edit']) && $_GET['edit'] == $row['userID']) ? 'selected' : '' ?>>
                <?= "{$row['userID']} - {$row['FirstName']} {$row['LastName']}" ?>
            </option>
        <?php endwhile; ?>
    </select>
    <button type="submit">Edit</button>
</form>

<!-- 📝 Edit form -->
<?php if ($editUser): ?>
    <hr>
    <h3>Editing: <?= $editUser['userID'] ?></h3>

    <form method="post" action="edit_user.php?edit=<?= $editUser['userID'] ?>">
        <input type="hidden" name="userID" value="<?= $editUser['userID'] ?>">

        First Name: <input type="text" name="FirstName" value="<?= htmlspecialchars($editUser['FirstName']) ?>" required><br>
        Last Name: <input type="text" name="LastName" value="<?= htmlspecialchars($editUser['LastName']) ?>" required><br>
        Email: <input type="email" name="Email" value="<?= htmlspecialchars($editUser['Email']) ?>" required><br>
        Password: <input type="text" name="Password" value="<?= htmlspecialchars($editUser['Password']) ?>" required><br>

        Role:
        <select name="Role" required>
            <option value="Customer" <?= $editUser['Role'] == 'Customer' ? 'selected' : '' ?>>Customer</option>
            <option value="Staff" <?= $editUser['Role'] == 'Staff' ? 'selected' : '' ?>>Staff</option>
            <option value="Admin" <?= $editUser['Role'] == 'Admin' ? 'selected' : '' ?>>Admin</option>
        </select><br><br>

        <button type="submit">💾 Save Changes</button>
    </form>

    <!-- 🚮 Delete Button -->
    <form method="post" onsubmit="return confirm('Are you sure you want to delete this user? This cannot be undone.');">
        <input type="hidden" name="delete_userID" value="<?= $editUser['userID'] ?>">
        <button type="submit" style="background-color:red; color:white;">🗑 Delete User</button>
    </form>
<?php endif; ?>

</body>
</html>
