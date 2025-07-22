<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "dbadm";

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Auto-generate userID like U00001, U00002, etc.
$sql = "SELECT userID FROM USERS ORDER BY userID DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $lastID = $row['userID'];
    $num = (int)substr($lastID, 1);  // Remove the 'U' and convert to int
    $num++;
    $userID = 'U' . str_pad($num, 5, '0', STR_PAD_LEFT);
} else {
    $userID = 'U00001';  // First user
}

// Get form data
$firstName = $_POST['FirstName'];
$lastName = $_POST['LastName'];
$password = password_hash($_POST['Password'], PASSWORD_DEFAULT);
$email = $_POST['Email'];
$role = $_POST['Role'];

// Prepare SQL and bind parameters
$stmt = $conn->prepare("INSERT INTO USERS (userID, FirstName, LastName, Password, Email, Role) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $userID, $firstName, $lastName, $password, $email, $role);

if ($stmt->execute()) {
    header("Location: ../html/login.html");
    exit();
} else {
    echo "<h3> Error: " . $stmt->error . "</h3>";
}

$stmt->close();
$conn->close();
?>
