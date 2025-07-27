<?php
session_start();
require_once 'db_connect.php';

// Redirect to login if not logged in
if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}

$userID = $_SESSION['userID'];

// Handle address update
if (isset($_POST['saveAddressEdit'])) {
    $newAddress = trim($_POST['editAddress']);
    
    // Prepare and execute update query
    $updateStmt = $conn->prepare('UPDATE USERS SET Address = ? WHERE userID = ?');
    if ($updateStmt) {
        $updateStmt->bind_param('ss', $newAddress, $userID);
        if ($updateStmt->execute()) {
            $address = $newAddress; // Update local variable
            $success_message = "✅ Address updated successfully";
        } else {
            $error_message = "❌ Failed to update address";
        }
        $updateStmt->close();
    }
}

// Handle account details update
if (isset($_POST['saveAccountEdit'])) {
    $newFirstName = trim($_POST['editFirstName']);
    $newLastName = trim($_POST['editLastName']);
    $newEmail = trim($_POST['editEmail']);
    $newPassword = trim($_POST['editPassword']);
    $confirmPassword = trim($_POST['editPasswordConfirm']);
    
    // Validate inputs
    $updateFields = array();
    $types = '';
    $params = array();
    
    if (!empty($newFirstName)) {
        $updateFields[] = "FirstName = ?";
        $types .= 's';
        $params[] = $newFirstName;
    }
    
    if (!empty($newLastName)) {
        $updateFields[] = "LastName = ?";
        $types .= 's';
        $params[] = $newLastName;
    }
    
    if (!empty($newEmail)) {
        $updateFields[] = "Email = ?";
        $types .= 's';
        $params[] = $newEmail;
    }
    
    // Handle password update
    if (!empty($newPassword)) {
        if ($newPassword === $confirmPassword) {
            $updateFields[] = "Password = ?";
            $types .= 's';
            $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
        } else {
            $error_message = "❌ Passwords do not match";
        }
    }
    
    // Only proceed if there are fields to update and no errors
    if (!empty($updateFields) && !isset($error_message)) {
        // Add userID to parameters
        $types .= 's';
        $params[] = $userID;
        
        // Create update query
        $sql = "UPDATE USERS SET " . implode(", ", $updateFields) . " WHERE userID = ?";
        
        // Prepare and execute
        $updateStmt = $conn->prepare($sql);
        if ($updateStmt) {
            // Create array reference for bind_param
            $bindParams = array($types);
            for ($i = 0; $i < count($params); $i++) {
                $bindParams[] = &$params[$i];
            }
            
            call_user_func_array(array($updateStmt, 'bind_param'), $bindParams);
            
            if ($updateStmt->execute()) {
                $success_message = "✅ Account details updated successfully";
                // Update local variables for display
                $firstName = !empty($newFirstName) ? $newFirstName : $firstName;
                $lastName = !empty($newLastName) ? $newLastName : $lastName;
                $email = !empty($newEmail) ? $newEmail : $email;
            } else {
                $error_message = "❌ Failed to update account details";
            }
            $updateStmt->close();
        }
    }
}

// Fetch user info
$stmt = $conn->prepare('SELECT FirstName, LastName, Email, Address FROM USERS WHERE userID = ? LIMIT 1');
$stmt->bind_param('s', $userID);
$stmt->execute();
$stmt->bind_result($firstName, $lastName, $email, $address);
$stmt->fetch();
$stmt->close();

// Get customer summary
$stmt = $conn->prepare("CALL get_customer_summary(?)");
$stmt->bind_param("s", $userID);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->next_result(); // Clear stored procedure results

// Get order history using stored procedure
$stmt = $conn->prepare("CALL get_user_orders(?)");
$stmt->bind_param("s", $userID);
$stmt->execute();
$orderHistory = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Page ‒ KALYE WEST</title>
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
        body { background: #fff; color: var(--brand-black); display: flex; flex-direction: column; }
        .page-wrapper { flex: 1; display: flex; flex-direction: column; }
        a { color: inherit; text-decoration: none; }
        
        /* Header Styles */
        header { 
            height: var(--nav-height); 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            padding: 0 24px; 
            border-bottom: 1px solid var(--mid-gray); 
        }
        .logo { flex: 1; display: flex; justify-content: center; margin: 20px 0; }
        .logo img { width: 150px; height: auto; transition: opacity 0.2s ease; margin-left: 100px; }
        .utils { display: flex; align-items: center; gap: 20px; }
        .utils select, .utils i { font-size: 14px; cursor: pointer; }

        /* Navigation Styles */
        nav { 
            display: flex; 
            justify-content: center; 
            gap: 50px; 
            margin-top: -1px; 
            border-bottom: 1px solid var(--mid-gray); 
        }
        nav a { padding: 18px 0; font-size: 13px; letter-spacing: .5px; }
        nav a.active { font-weight: bold; border-bottom: 2px solid var(--brand-black); }

        /* Main Content Styles */
        main { 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            gap: 30px; 
            padding: 40px; 
            width: 100%; 
            max-width: none; 
        }
        .account-content { 
            display: flex; 
            justify-content: space-between; 
            gap: 20px; 
            width: 100%; 
        }

        /* Account Section Styles */
        .order-history, .account-details { 
            background: white; 
            border: 1px solid var(--mid-gray); 
            padding: 20px; 
            border-radius: 4px; 
        }
        .order-history { width: 100%; margin-right: 20px; }
        .account-details { width: 28%; }
        .section-title { font-weight: bold; margin-bottom: 10px; }
        .edit-btn { float: right; cursor: pointer; }

        /* Forms and Inputs */
        .address-edit, .account-edit { display: none; margin-top: 10px; }
        input[type="text"], 
        input[type="email"], 
        input[type="password"],
        textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid var(--mid-gray);
            border-radius: 4px;
        }
        button {
            padding: 8px 16px;
            background: var(--brand-black);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        button:hover { opacity: 0.9; }
        .cancel-btn {
            background: var(--light-gray);
            color: var(--brand-black);
            margin-left: 10px;
        }

        /* Messages */
        .success-message {
            color: green;
            padding: 10px;
            margin-bottom: 10px;
            background: #e8f5e9;
            border-radius: 4px;
        }
        .error-message {
            color: red;
            padding: 10px;
            margin-bottom: 10px;
            background: #ffebee;
            border-radius: 4px;
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

        /* Order History Styles */
        .order-header {
            font-weight: bold;
            color: #666;
            font-size: 14px;
        }

        .order-row {
            transition: background-color 0.2s;
        }

        .order-row:hover {
            background-color: var(--light-gray);
        }

        .order-id strong {
            color: var(--brand-black);
            font-size: 14px;
        }

        .order-id small {
            color: #666;
            font-size: 12px;
        }

        .status-badge {
            display: inline-block;
            text-transform: uppercase;
            font-weight: 500;
        }

        .delivery {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <header>
            <div class="logo">
                <a href="HOME_Homepage.php">
                    <img src="../Logos/KW Logo.png" alt="KALYE WEST">
                </a>
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

        <nav>
            <a href="CATEGORY_Tees.php">TEES</a>
            <a href="CATEGORY_Bottoms.php">BOTTOMS</a>
            <a href="CATEGORY_Layering.php">LAYERING</a>
        </nav>

        <main>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2 style="font-size: 24px; font-weight: bold;">ACCOUNT</h2>
                <a class="logout" href="logout.php">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>

            <div class="account-content">
                <div class="order-history">
                    <h3 style="text-align:center; margin-bottom: 40px;">ORDER HISTORY</h3>
                    <?php if ($orderHistory->num_rows === 0): ?>
                        <p style="text-align: center; padding: 40px 0; color: #888;">
                            You haven't placed any orders yet.
                        </p>
                    <?php else: ?>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="text-align: left; padding: 12px; border-bottom: 1px solid var(--mid-gray);">Order ID</th>
                                    <th style="text-align: left; padding: 12px; border-bottom: 1px solid var(--mid-gray);">Products</th>
                                    <th style="text-align: left; padding: 12px; border-bottom: 1px solid var(--mid-gray);">Total</th>
                                    <th style="text-align: left; padding: 12px; border-bottom: 1px solid var(--mid-gray);">Status</th>
                                    <th style="text-align: left; padding: 12px; border-bottom: 1px solid var(--mid-gray);">Order Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $orderHistory->fetch_assoc()): 
                                    // Get currency symbol
                                    $symbol = match ($order['Currency']) {
                                        'PHP' => '₱',
                                        'USD' => '$',
                                        'WON' => '₩',
                                        default => ''
                                    };

                                    // Fetch product details for this order
                                    $itemsStmt = $conn->prepare("
                                        SELECT P.ProductName as Name, CI.QuantityOrdered, P.Size 
                                        FROM CART_ITEMS CI
                                        JOIN PRODUCT P ON CI.ref_productID = P.productID 
                                        WHERE CI.ref_cartID = ?
                                    ");
                                    $itemsStmt->bind_param("s", $order['cartID']);
                                    $itemsStmt->execute();
                                    $items = $itemsStmt->get_result();
                                    $products = "";
                                    while ($item = $items->fetch_assoc()) {
                                        $products .= "<div style='margin-bottom:5px;'><strong>{$item['Name']}</strong><br>
                                                    Qty: {$item['QuantityOrdered']} | Size: {$item['Size']}</div>";
                                    }
                                    $itemsStmt->close();
                                ?>
                                    <tr>
                                        <td style="padding: 12px; border-bottom: 1px solid var(--light-gray);">
                                            <?= htmlspecialchars($order['cartID']) ?>
                                        </td>
                                        <td style="padding: 12px; border-bottom: 1px solid var(--light-gray);">
                                            <?= $products ?>
                                        </td>
                                        <td style="padding: 12px; border-bottom: 1px solid var(--light-gray);">
                                            <?= $symbol . number_format($order['Total'], 2) ?>
                                        </td>
                                        <td style="padding: 12px; border-bottom: 1px solid var(--light-gray);">
                                            <span class="status-badge" style="
                                                padding: 5px 10px;
                                                border-radius: 12px;
                                                font-size: 12px;
                                                background-color: <?= $order['Status'] === 'Delivered' ? '#e8f5e9' : 
                                                                    ($order['Status'] === 'In Transit' ? '#fff3e0' : 
                                                                    ($order['Status'] === 'To Ship' ? '#e3f2fd' : '#f5f5f5')) ?>;
                                                color: <?= $order['Status'] === 'Delivered' ? '#2e7d32' : 
                                                        ($order['Status'] === 'In Transit' ? '#ef6c00' : 
                                                        ($order['Status'] === 'To Ship' ? '#1565c0' : '#616161')) ?>;
                                            ">
                                                <?= htmlspecialchars($order['Status']) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 12px; border-bottom: 1px solid var(--light-gray);">
                                            <?= date('M d, Y', strtotime($order['Order_Date'])) ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <div class="account-details">
                    <div class="section-title">
                        ACCOUNT DETAILS 
                        <i class="fa-solid fa-pen edit-btn" onclick="toggleEdit('account')"></i>
                    </div>
                    <div id="accountDisplay">
                        <p id="userName"><?= htmlspecialchars($firstName . ' ' . $lastName) ?></p>
                        <p id="userEmail"><?= htmlspecialchars($email) ?></p>
                    </div>
                    <div id="accountEdit" class="account-edit">
                        <form method="POST" action="">
                            <input type="text" name="editFirstName" value="<?= htmlspecialchars($firstName) ?>" placeholder="First name">
                            <input type="text" name="editLastName" value="<?= htmlspecialchars($lastName) ?>" placeholder="Last name">
                            <input type="email" name="editEmail" value="<?= htmlspecialchars($email) ?>" placeholder="Email">
                            <input type="password" name="editPassword" placeholder="New Password">
                            <input type="password" name="editPasswordConfirm" placeholder="Confirm Password">
                            <button type="submit" name="saveAccountEdit">Save</button>
                            <button type="button" class="cancel-btn" onclick="cancelEdit('account')">Cancel</button>
                        </form>
                    </div>

                    <div class="section-title" style="margin-top: 40px;">
                        ADDRESS 
                        <i class="fa-solid fa-pen edit-btn" onclick="toggleEdit('address')"></i>
                    </div>
                    <div id="addressDisplay">
                        <?php if (isset($success_message)): ?>
                            <p class="success-message"><?= $success_message ?></p>
                        <?php endif; ?>
                        <?php if (isset($error_message)): ?>
                            <p class="error-message"><?= $error_message ?></p>
                        <?php endif; ?>
                        <p id="userAddress"><?= htmlspecialchars($address) ?></p>
                    </div>
                    <div id="addressEdit" class="address-edit">
                        <form method="POST" action="">
                            <textarea name="editAddress" rows="3" placeholder="Enter your address..."><?= htmlspecialchars($address) ?></textarea>
                            <button type="submit" name="saveAddressEdit">Save</button>
                            <button type="button" class="cancel-btn" onclick="cancelEdit('address')">Cancel</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
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

    <script>
        function toggleEdit(section) {
            document.getElementById(section + 'Display').style.display = 'none';
            document.getElementById(section + 'Edit').style.display = 'block';
        }

        function cancelEdit(section) {
            document.getElementById(section + 'Display').style.display = 'block';
            document.getElementById(section + 'Edit').style.display = 'none';
            
            // Reset form if canceling account edit
            if (section === 'account') {
                const form = document.getElementById('accountEdit').querySelector('form');
                form.reset();
            }
        }

        // Auto-hide messages after 3 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.success-message, .error-message');
            messages.forEach(function(message) {
                message.style.display = 'none';
            });
        }, 3000);
    </script>
    
</body>
</html>
