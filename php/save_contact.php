<?php
session_start();

if (!isset($_SESSION['userID'])) {
    echo "Access denied. Please <a href='../html/login.html'>login</a>.";
    exit();
}

$host = "localhost";
$user = "root";
$password = "";
$dbname = "dbadm";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Auto-generate userContactID like UC00001
$sql = "SELECT userContactID FROM USERS_CONTACT ORDER BY userContactID DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $lastID = $row['userContactID'];
    $num = (int)substr($lastID, 2);
    $num++;
    $userContactID = 'UC' . str_pad($num, 5, '0', STR_PAD_LEFT);
} else {
    $userContactID = 'UC00001';
}

$ref_userID = $_SESSION['userID'];
$address = $_POST['Address'];
$postal = $_POST['Postal'];
$city = $_POST['City'];
$region = $_POST['Region'];
$phone = $_POST['Phone'];

$stmt = $conn->prepare("INSERT INTO USERS_CONTACT (userContactID, ref_userID, Address, Postal, City, Region, Phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $userContactID, $ref_userID, $address, $postal, $city, $region, $phone);

if ($stmt->execute()) {
   header("Location: ../html/product_upload.html");
    exit();
} else {
    echo "<h3>❌ Error: " . $stmt->error . "</h3>";
}

$stmt->close();
$conn->close();
?>
