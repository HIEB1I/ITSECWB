<?php
// get_product_image.php: Outputs the image BLOB for a given productID
if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('Missing product ID');
}

require_once 'db_connect.php';

$id = $_GET['id'];
$stmt = $conn->prepare('SELECT Image FROM PRODUCT WHERE productID = ? LIMIT 1');
$stmt->bind_param('s', $id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($imageData);
    $stmt->fetch();
    if ($imageData !== null) {
        header('Content-Type: image/png');
        echo $imageData;
        exit;
    }
}
// If not found or no image, output a 1x1 transparent PNG
header('Content-Type: image/png');
echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/w8AAn8B9pQn2wAAAABJRU5ErkJggg=='); 