<?php
// Any Role
require_once 'auth_check.php';
requireLogin(); // any logged-in role
require_once 'db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}

$userID = $_SESSION['userID'];

// Get user's latest cart that was purchased
$stmt = $conn->prepare("
    SELECT C.*, U.FirstName, U.LastName, U.Address 
    FROM CART C
    JOIN USERS U ON C.ref_userID = U.userID
    WHERE C.ref_userID = ? AND C.Purchased = TRUE 
    ORDER BY C.cartID DESC 
    LIMIT 1
");

$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();
$orderInfo = $result->fetch_assoc();
$stmt->close();

if (!$orderInfo) {
    header('Location: HOME_Homepage.php');
    exit();
}

// Get cart items
$stmt = $conn->prepare("
    SELECT CI.QuantityOrdered, P.ProductName, P.Price, P.Image, P.Size
    FROM CART_ITEMS CI
    JOIN PRODUCT P ON CI.ref_productID = P.productID
    WHERE CI.ref_cartID = ?
");

$stmt->bind_param("s", $orderInfo['cartID']);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate total
$total = 0;
foreach ($items as $item) {
    $total += ($item['Price'] * $item['QuantityOrdered']);
}

function getImageTag($imageData, $alt = '', $class = '') {
    if ($imageData) {
        $imgData = base64_encode($imageData);
        return "<img src='data:image/png;base64,{$imgData}' alt='" . htmlspecialchars($alt) . "' class='" . htmlspecialchars($class) . "'>";
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation ‒ KALYE WEST</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { 
            --nav-height: 110px; 
            --brand-black: #000; 
            --light-gray: #f5f5f7; 
            --mid-gray: #dadada; 
            font-family: Arial, Helvetica, sans-serif; 
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        html, body { height: 100%; }
        
        body { 
            background: #fff; 
            color: var(--brand-black); 
            display: flex; 
            flex-direction: column; 
        }
        
        /* Header */
        header { 
            height: var(--nav-height); 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            padding: 0 24px; 
            border-bottom: 1px solid var(--mid-gray); 
        }
        
        .logo { 
            flex: 1; 
            display: flex; 
            justify-content: center; 
        }
        
        .logo img { 
            width: 150px; 
            height: auto; 
            transition: opacity 0.2s ease; 
        }
        
        .logo img:hover { 
            opacity: 0.7; 
        }

        /* Main Content */
        .confirmation-container {
            flex: 1;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }

        .confirmation-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .confirmation-header h1 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .order-details {
            background: white;
            border: 1px solid var(--mid-gray);
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .order-details h3 {
            margin-bottom: 20px;
            font-size: 18px;
        }

        .order-details p {
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .order-items {
            margin-top: 30px;
        }

        .order-items h4 {
            margin-bottom: 20px;
            font-size: 16px;
        }

        .item {
            display: flex;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid var(--light-gray);
        }

        .item:last-child {
            border-bottom: none;
        }

        .product-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }

        .item-details {
            flex: 1;
            line-height: 1.6;
        }

        .item-details strong {
            font-size: 16px;
            display: block;
            margin-bottom: 5px;
        }

        .total-line {
            text-align: right;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid var(--light-gray);
            font-size: 18px;
        }

        .actions {
            text-align: center;
            margin-top: 40px;
        }

        .continue-shopping {
            display: inline-block;
            padding: 15px 30px;
            background: var(--brand-black);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: opacity 0.2s;
        }

        .continue-shopping:hover {
            opacity: 0.9;
        }

        /* Footer */
        footer {
            padding: 30px;
            text-align: center;
            border-top: 1px solid var(--mid-gray);
        }

        footer .social {
            margin-bottom: 15px;
        }

        footer .social a {
            color: var(--brand-black);
            text-decoration: none;
            margin: 0 10px;
        }

        footer .social i {
            font-size: 18px;
            transition: opacity 0.2s;
        }

        footer .social i:hover {
            opacity: 0.7;
        }

        footer .copyright {
            font-size: 14px;
            color: #666;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .confirmation-container {
                padding: 15px;
            }

            .order-details {
                padding: 20px;
            }

            .item {
                flex-direction: column;
                gap: 15px;
            }

            .product-img {
                width: 100%;
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <a href="HOME_Homepage.php">
                <img src="../Logos/KW Logo.png" alt="KALYE WEST">
            </a>
        </div>
    </header>

    <div class="confirmation-container">
        <div class="confirmation-header">
            <h1>Thank you for your order!</h1>
            <p>Your order has been placed successfully.</p>
        </div>

        <div class="order-details">
            <h3>Order Details</h3>
            <p><strong>Name:</strong> <?= htmlspecialchars($orderInfo['FirstName'] . ' ' . $orderInfo['LastName']) ?></p>
            <p><strong>Shipping Address:</strong> <?= htmlspecialchars($orderInfo['Address']) ?></p>
            <p><strong>Payment Method:</strong> <?= htmlspecialchars($orderInfo['MOP']) ?></p>

            <div class="order-items">
                <h4>Items Ordered:</h4>
                <?php foreach ($items as $item): ?>
                <div class="item">
                    <?= getImageTag($item['Image'], $item['ProductName'], 'product-img') ?>
                    <div class="item-details">
                        <strong><?= htmlspecialchars($item['ProductName']) ?></strong><br>
                        Size: <?= htmlspecialchars($item['Size']) ?><br>
                        Quantity: <?= $item['QuantityOrdered'] ?><br>
                        Price: ₱<?= number_format($item['Price'], 2) ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="total-line">
                    <strong>Total: ₱<?= number_format($total, 2) ?></strong>
                </div>
            </div>
        </div>

        <div class="actions">
            <a href="HOME_Homepage.php" class="continue-shopping">Continue Shopping</a>
        </div>
    </div>

    <footer>
        <div class="social">
            <a href="https://www.facebook.com/mnlaofficial/" target="_blank">
                <i class="fa-brands fa-facebook"></i>
            </a>
            <a href="https://www.instagram.com/mnlaofficial/?hl=en" target="_blank">
                <i class="fa-brands fa-instagram"></i>
            </a>
            <a href="https://www.tiktok.com/@mnlaofficial" target="_blank">
                <i class="fa-brands fa-tiktok"></i>
            </a>
        </div>
        <div class="copyright">2025, KALYE WEST</div>
    </footer>
</body>
</html>