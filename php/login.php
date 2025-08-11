<?php
session_start();

// default message container and prefill
$error_message = '';
$email_prefill = '';

// POST handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get inputs (trim email)
    $email_prefill = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic required-field check (visible error)
    if ($email_prefill === '' || $password === '') {
        $error_message = "❌ Please fill in both email and password.";
    } else {
        // Connect to DB (use proper credentials)
        $conn = new mysqli("localhost", "root", "", "dbadm");
        if ($conn->connect_error) {
            error_log("DB Connection failed: " . $conn->connect_error);
            // fail securely
            $error_message = "Service temporarily unavailable. Please try again later.";
        } else {

            // 1) Attempt to look up the user by email (we'll use the returned fields if user exists)
            $stmt = $conn->prepare("
                SELECT userID, Password, Role, FailedAttempts, LockoutUntil
                FROM USERS
                WHERE Email = ?
            ");
            $stmt->bind_param("s", $email_prefill);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc(); // null if not found
            $stmt->close();

            // Initialize these so they exist in all code paths
            $failedAttempts = 0;
            $lockoutUntil = null;

            if ($user) {
                $failedAttempts = (int)$user['FailedAttempts'];
                $lockoutUntil = $user['LockoutUntil'];
            }

            // 2) Check lockout (if user exists). If locked, show generic locked message.
            if ($user && $lockoutUntil && strtotime($lockoutUntil) > time()) {
                $error_message = "❌ Account locked due to multiple failed login attempts. Try again later.";
            } else {
                // 3) If user found, verify password
                $login_success = false;
                if ($user && password_verify($password, $user['Password'])) {
                    // Success: reset FailedAttempts and LockoutUntil, set session and redirect by role
                    $reset = $conn->prepare("UPDATE USERS SET FailedAttempts = 0, LockoutUntil = NULL WHERE Email = ?");
                    $reset->bind_param("s", $email_prefill);
                    $reset->execute();
                    $reset->close();

                    // Set session
                    $_SESSION['userID'] = $user['userID'];
                    $_SESSION['role']   = $user['Role'];

                    // Role-based redirect (Admin/Staff -> admin area, Customer -> home)
                    if ($user['Role'] === 'Admin' || $user['Role'] === 'Staff') {
                        header("Location: ADMIN_Dashboard.php");
                        exit();
                    } else {
                        header("Location: HOME_Homepage.php");
                        exit();
                    }
                } else {
                    // 4) Failed login: increment FailedAttempts only if user exists
                    if ($user) {
                        $newAttempts = $failedAttempts + 1;
                        if ($newAttempts >= 5) {
                            // lock for 1 minute
                            $newLockout = date("Y-m-d H:i:s", time() + 1 * 60);
                            $upd = $conn->prepare("UPDATE USERS SET FailedAttempts = ?, LockoutUntil = ? WHERE Email = ?");
                            $upd->bind_param("iss", $newAttempts, $newLockout, $email_prefill);
                        } else {
                            $upd = $conn->prepare("UPDATE USERS SET FailedAttempts = ? WHERE Email = ?");
                            $upd->bind_param("is", $newAttempts, $email_prefill);
                        }
                        $upd->execute();
                        $upd->close();
                    } else {
                        // If no such user, do a dummy password_verify to help avoid timing attacks
                        // (makes the response time more consistent so attackers can't easily probe existence)
                        password_verify($password, password_hash("fake-password", PASSWORD_DEFAULT));
                    }

                    // Generic error message (Requirement #4)
                    $error_message = "❌ Invalid username and/or password.";
                }
            } // end else not locked

            $conn->close();
        }
    } 
} 
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - Clothing Store</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      text-align: center;
      background-color: white;
      margin: 0;
      padding: 0;
    }

    .logo {
      margin-top: 5px;
      margin-bottom: 5px;
    }

    .logo img {
      width: 400px;
      height: auto;
    }

    hr {
      width: 90%;
      margin: 5px auto;
    }

    .login-container {
      max-width: 400px;
      margin: auto;
    }

    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 12px;
      margin: 8px 0;
      box-sizing: border-box;
      border: 1px solid #000;
    }

    .sign-in-btn {
      background-color: black;
      color: white;
      border: none;
      padding: 10px 30px;
      margin: 10px 0;
      cursor: pointer;
    }

    .guest-btn {
      border: 1px solid black;
      background-color: white;
      padding: 10px 30px;
      cursor: pointer;
      margin-top: 20px;
    }

    .footer-logos {
      display: flex;
      justify-content: space-around;
      align-items: center;
      flex-wrap: wrap;
      margin-top: 80px;
      padding: 10px;
      border-top: 1px solid #ccc;
    }

    .footer-logos img {
      height: 50px;
      max-width: 100px;
      margin: 10px;
      object-fit: contain;
    }

    a {
      color: black;
      text-decoration: none;
      font-size: 12px;
    }

    .error-message {
      color: red;
      margin-top: 20px;
    }
  </style>
</head>
<body>
  <!-- KW Logo -->
  <div class="logo">
    <img src="../Logos/KW Logo.png" alt="Kanye West Logo">
  </div>

  <hr>

  <!-- Login Form -->
  <div class="login-container">
    <h2>Login</h2>
    <form method="post" action="">
      <input type="email" name="email" placeholder="Email Address" required>
      <input type="password" name="password" placeholder="Password" required>
      <div style="text-align: left; margin-top: 5px; margin-bottom: 10px;">
        <a href="#">Forgot Password?</a>
      </div>
      <button class="sign-in-btn" type="submit">Sign in</button>
    </form>

    <?php if (isset($error_message)) { ?>
      <div class="error-message">
        <?php echo $error_message; ?>
      </div>
    <?php } ?>

    <div><a href="../php/register.php">Create an account</a></div>
    <hr style="margin: 30px 0;">
    <button class="guest-btn" onclick="location.href='GUEST_Homepage.php'">Continue as guest →</button>
  </div>

  <!-- Footer Logos -->
  <div class="footer-logos">
    <img src="../Logos/Femme Logo.png" alt="FEMME">
    <img src="../Logos/Little Logo.png" alt="little human">
    <img src="../Logos/MNLA Logo.png" alt="MN+LA">
    <img src="../Logos/Sage Logo.png" alt="SageHill.">
    <img src="../Logos/Daily Logo.png" alt="Daily Flight">
  </div>
</body>
</html>
