<?php
session_start();

// Database credentials
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

// ACID-Compliant Cart Creation Algorithm
// A - Atomicity: All cart operations succeed or fail together
// C - Consistency: Foreign keys and data integrity maintained
// I - Isolation: User sessions don't interfere with each other
// D - Durability: Changes are committed to database permanently

// If the user is not logged in, redirect to login page
if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

// ACID Algorithm: Cart Creation with Transaction Management
if (!isset($_SESSION['cartID'])) {
    try {
        // ATOMICITY: Start transaction - all operations succeed or fail together
        $conn->autocommit(FALSE);
        $conn->begin_transaction();
        
        // ISOLATION: Lock user's cart records to prevent concurrent modifications
        $stmt_check = $conn->prepare("
            SELECT cartID 
            FROM CART 
            WHERE ref_userID = ? AND Purchased = FALSE 
            FOR UPDATE
        ");
        $stmt_check->bind_param("s", $_SESSION['userID']);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        $existing_cart = $result->fetch_assoc();
        $stmt_check->close();
        
        if ($existing_cart) {
            // CONSISTENCY: Use existing active cart
            $_SESSION['cartID'] = $existing_cart['cartID'];
        } else {
            // ATOMICITY: Create new cart with proper ID generation
            $max_attempts = 3;
            $attempt = 0;
            $cartID = null;
            
            while ($attempt < $max_attempts && !$cartID) {
                // Generate sequential cart ID safely
                $stmt_next_id = $conn->prepare("
                    SELECT CONCAT('C', LPAD(IFNULL(MAX(CAST(SUBSTRING(cartID, 2) AS UNSIGNED)), 0) + 1, 5, '0')) AS next_cartID 
                    FROM CART 
                    FOR UPDATE
                ");
                $stmt_next_id->execute();
                $result = $stmt_next_id->get_result();
                $row = $result->fetch_assoc();
                $potential_cartID = $row['next_cartID'];
                $stmt_next_id->close();
                
                // CONSISTENCY: Verify foreign key constraint (userID exists)
                $stmt_verify_user = $conn->prepare("SELECT userID FROM USER WHERE userID = ?");
                $stmt_verify_user->bind_param("s", $_SESSION['userID']);
                $stmt_verify_user->execute();
                $user_exists = $stmt_verify_user->get_result()->fetch_assoc();
                $stmt_verify_user->close();
                
                if (!$user_exists) {
                    throw new Exception('Invalid user session. Please login again.');
                }
                
                // ATOMICITY: Insert new cart
                $stmt_create = $conn->prepare("
                    INSERT INTO CART (cartID, Total, Purchased, ref_userID, created_at) 
                    VALUES (?, 0.00, FALSE, ?, NOW())
                ");
                $stmt_create->bind_param("ss", $potential_cartID, $_SESSION['userID']);
                
                if ($stmt_create->execute()) {
                    $cartID = $potential_cartID;
                    $_SESSION['cartID'] = $cartID;
                    break;
                } else {
                    // Handle duplicate key error (race condition)
                    if ($conn->errno == 1062) { // Duplicate entry error
                        $attempt++;
                        continue;
                    } else {
                        throw new Exception('Cart creation failed: ' . $conn->error);
                    }
                }
                $stmt_create->close();
            }
            
            if (!$cartID) {
                throw new Exception('Failed to create cart after multiple attempts');
            }
        }
        
        // DURABILITY: Commit transaction to make changes permanent
        $conn->commit();
        $conn->autocommit(TRUE);
        
        // Success - cart is ready
        error_log("Cart created/retrieved successfully: " . $_SESSION['cartID'] . " for user: " . $_SESSION['userID']);
        
    } catch (Exception $e) {
        // ATOMICITY: Rollback all changes on any failure
        $conn->rollback();
        $conn->autocommit(TRUE);
        
        // Log error for debugging
        error_log("ACID Cart Creation Error: " . $e->getMessage());
        
        // Clear potentially corrupted session data
        unset($_SESSION['cartID']);
        
        // User-friendly error
        die('Unable to initialize shopping cart. Please try refreshing the page.');
    }
}

// CONSISTENCY: Validate cart still exists and belongs to user
if (isset($_SESSION['cartID'])) {
    try {
        $stmt_validate = $conn->prepare("
            SELECT cartID 
            FROM CART 
            WHERE cartID = ? AND ref_userID = ? AND Purchased = FALSE
        ");
        $stmt_validate->bind_param("ss", $_SESSION['cartID'], $_SESSION['userID']);
        $stmt_validate->execute();
        $valid_cart = $stmt_validate->get_result()->fetch_assoc();
        $stmt_validate->close();
        
        if (!$valid_cart) {
            // Cart is invalid, clear session and recreate
            unset($_SESSION['cartID']);
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    } catch (Exception $e) {
        error_log("Cart validation error: " . $e->getMessage());
        unset($_SESSION['cartID']);
    }
}

// Rest of your existing code for fetching products...
// [Product fetching code remains the same]

$conn->close();
?>