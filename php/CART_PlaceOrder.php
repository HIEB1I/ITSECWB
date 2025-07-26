<?php
session_start();
require_once 'db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}

$userID = $_SESSION['userID'];
$error_message = '';

// Fetch cart details
if (isset($_POST['cartID'])) {
    $cartID = $_POST['cartID'];
    
    // Get cart items
    $stmt = $conn->prepare("
        SELECT CI.cartItemsID, P.ProductName, P.Price, CI.QuantityOrdered, 
               (P.Price * CI.QuantityOrdered) AS SubTotal, P.Image, P.Size 
        FROM CART_ITEMS CI 
        JOIN PRODUCT P ON CI.ref_productID = P.productID 
        WHERE CI.ref_cartID = ?
    ");
    $stmt->bind_param("s", $cartID);
    $stmt->execute();
    $cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Get user details
    $stmt = $conn->prepare("SELECT FirstName, LastName, Email, Address FROM USERS WHERE userID = ?");
    $stmt->bind_param("s", $userID);
    $stmt->execute();
    $userInfo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Calculate total
    $total = 0;
    foreach ($cartItems as $item) {
        $total += $item['SubTotal'];
    }
} else {
    header('Location: CART_ViewCart.php');
    exit();
}

// Handle order placement
if (isset($_POST['placeOrder'])) {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $address = trim($_POST['address']);
    $paymentMethod = $_POST['payment'];
    
    // Update user details first
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Update user information
        $updateUser = $conn->prepare("
            UPDATE USERS 
            SET FirstName = ?, LastName = ?, Address = ? 
            WHERE userID = ?
        ");
        $updateUser->bind_param("ssss", $firstName, $lastName, $address, $userID);
        $updateUser->execute();
        
        // Set required variables for checkout.php
        $_POST['currency'] = 'PHP';
        $_POST['payment_method'] = $paymentMethod;
        
        // Include checkout.php to handle stock check and cart update
        require 'checkout.php';
        
        // If we get here, checkout was successful
        header("Location: CART_OrderConfirmation.php");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error placing order: " . $e->getMessage();
    }
}

function getImageTag($imageData, $alt = '', $class = 'product-img') {
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
    <title>Check Out ‒ KALYE WEST</title>
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
            min-height: 100vh;
        }
        
        /* Header Styles */
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
            margin: 20px 0; 
        }
        
        .logo img { 
            width: 150px; 
            height: auto; 
            transition: opacity 0.2s ease; 
            margin-left: 100px; 
        }
        
        .utils { 
            display: flex; 
            align-items: center; 
            gap: 20px; 
        }
        
        .utils select, .utils i { 
            font-size: 14px; 
            cursor: pointer; 
        }

        /* Checkout Container */
        .checkout-container {
            flex: 1;
            display: flex;
            padding: 40px;
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Form Section */
        .form-section {
            flex: 2;
            background: white;
            padding: 30px;
            border-radius: 8px;
            border: 1px solid var(--mid-gray);
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group strong {
            display: block;
            margin-bottom: 8px;
        }

        .form-group small {
            color: #666;
            display: block;
            margin-bottom: 12px;
        }

        .inline-inputs {
            display: flex;
            gap: 12px;
        }

        input[type="text"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--mid-gray);
            border-radius: 4px;
            font-size: 14px;
        }

        /* Summary Section */
        .summary-section {
            flex: 1;
            background: white;
            padding: 30px;
            border-radius: 8px;
            border: 1px solid var(--mid-gray);
            align-self: flex-start;
        }

        .item {
            display: flex;
            gap: 15px;
            margin: 20px 0;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .product-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }

        .item-details {
            font-size: 14px;
            line-height: 1.4;
        }

        .total-line {
            margin: 20px 0;
            padding-top: 20px;
            border-top: 2px solid var(--light-gray);
            font-size: 16px;
            text-align: right;
        }

        /* Payment Options */
        .payment-options {
            margin-top: 12px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .payment-options label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px;
            border: 1px solid var(--mid-gray);
            border-radius: 4px;
            cursor: pointer;
        }

        .payment-options label:hover {
            background: var(--light-gray);
        }

        /* Buttons */
        .place-order-btn {
            width: 100%;
            padding: 16px;
            background: var(--brand-black);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .place-order-btn:hover {
            opacity: 0.9;
        }

        /* Error Messages */
        .error {
            color: #d32f2f;
            background: #ffebee;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        /* Footer */
        footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            border-top: 1px solid var(--mid-gray);
            font-size: 14px;
        }

        footer .social i {
            margin: 0 10px;
            font-size: 18px;
            cursor: pointer;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .checkout-container {
                flex-direction: column;
                padding: 20px;
            }

            .summary-section {
                width: 100%;
            }

            .inline-inputs {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <a href="HOME_Homepage.php"><img src="../Logos/KW Logo.png" alt="KALYE WEST"></a>
        </div>
        <div class="utils">
            <select>
                <option selected>Php</option>
                <option>USD</option>
                <option>KRW</option>
            </select>
            <a href="PROFILE_User.php"><i class="fa-regular fa-user"></i></a>
            <a href="CART_ViewCart.php"><i class="fa-solid fa-bag-shopping"></i></a>
        </div>
    </header>

    <div class="checkout-container">
        <div class="form-section">
            <h2>Check out</h2>
            <?php if ($error_message): ?>
                <div class="error"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">Account<br>
                    <strong><?= htmlspecialchars($userInfo['Email']) ?></strong>
                </div>
                
                <div class="form-group inline-inputs">
                    <input type="text" name="firstName" placeholder="First name" 
                           value="<?= htmlspecialchars($userInfo['FirstName']) ?>" required>
                    <input type="text" name="lastName" placeholder="Last name" 
                           value="<?= htmlspecialchars($userInfo['LastName']) ?>" required>
                </div>
                
                <div class="form-group">
                    <strong>Shipping Address</strong>
                    <textarea name="address" rows="3" placeholder="Enter your complete shipping address" required><?= htmlspecialchars($userInfo['Address']) ?></textarea>
                </div>

                <div class="form-group">
                    <strong>Payment</strong><br>
                    <small>All transactions are secure and encrypted.</small>
                    <div class="payment-options">
                        <label><input type="radio" name="payment" value="GCash"> G-CASH/BANK</label>
                        <label><input type="radio" name="payment" value="COD" checked> Cash on Delivery (COD)</label>
                    </div>
                </div>
                
                <input type="hidden" name="cartID" value="<?= htmlspecialchars($cartID) ?>">
            </div>

            <div class="summary-section">
                <h3>Order Summary</h3>
                <?php foreach ($cartItems as $item): ?>
                    <div class="item">
                        <?= getImageTag($item['Image'], $item['ProductName']) ?>
                        <div class="item-details">
                            <?= htmlspecialchars($item['ProductName']) ?><br>
                            Size: <?= htmlspecialchars($item['Size']) ?><br>
                            ₱<?= number_format($item['Price'], 2) ?> × <?= $item['QuantityOrdered'] ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="total-line">
                    Total: <strong>₱<?= number_format($total, 2) ?></strong>
                </div>
                
                <button type="submit" name="placeOrder" class="place-order-btn">Place order</button>
            </div>
        </form>
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