<?php
session_start();
date_default_timezone_set('Asia/Manila');

require_once 'security_logger.php';

$error_message = '';
$email_prefill = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_prefill = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email_prefill === '' || $password === '') {
        $error_message = "❌ Please fill in both email and password.";
    } else {
        $conn = new mysqli("localhost", "root", "", "dbadm");
        if ($conn->connect_error) {
            error_log("DB Connection failed: " . $conn->connect_error);
            header("Location: error_pages/maintenance.php");
            exit();
        } else {

          $logger = new SecurityLogger($conn);
          
            $now = date("Y-m-d H:i:s");
            $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

            // Fetch user and previous LastLoginAttempt
            $stmt = $conn->prepare("
                SELECT userID, Password, Role, FailedAttempts, LockoutUntil, LastLoginAttempt, LastLoginStatus
                FROM USERS
                WHERE Email = ?
            ");
            $stmt->bind_param("s", $email_prefill);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            // Save previous last-login timestamp 
            if ($user) {
                // Store the previous last attempt info before updating
                $_SESSION['prevLastAttempt'] = $user['LastLoginAttempt'];
                $_SESSION['prevLastStatus']  = $user['LastLoginStatus'];

                $failedAttempts = (int)($user['FailedAttempts'] ?? 0);
                $lockoutUntil   = $user['LockoutUntil'] ?? null;
            } else {
                $failedAttempts = 0;
                $lockoutUntil = null;
            }

            // Locked account handling: record attempt and show generic locked message
            if ($user && $lockoutUntil && strtotime($lockoutUntil) > time()) {
              // Record unsuccessful attempt
                $upd = $conn->prepare("UPDATE USERS SET LastLoginAttempt = ?, LastLoginIP = ?, LastLoginStatus = 'unsuccessful' WHERE Email = ?");
                $upd->bind_param("sss", $now, $ip, $email_prefill);
                $upd->execute();
                $upd->close();
                
                // LOGGING: unsucessful
                $logger->logAuthFailure("Login attempt on locked account", $email_prefill);

                header("Location: error_pages/account_locked.php");
                exit();
            } else {
                // Password verification
                if ($user && password_verify($password, $user['Password'])) {
                // if ($password === $user['Password']) {
                //if ($user && password_verify($password, $user['Password'])) {
                if ($password === $user['Password']) {
                    // Successful login — reset counters
                    $upd = $conn->prepare("UPDATE USERS SET FailedAttempts = 0, LockoutUntil = NULL, LastLoginAttempt = ?, LastLoginIP = ?, LastLoginStatus = 'successful' WHERE Email = ?");
                    $upd->bind_param("sss", $now, $ip, $email_prefill);
                    $upd->execute();
                    $upd->close();
                      
                    // LOGGING: sucessful
                    $logger->logAuthSuccess($user['userID'], $user['Role'], "User logged in successfully.");

                    // Set session for logged-in user
                    $_SESSION['userID'] = $user['userID'];
                    $_SESSION['role']   = $user['Role'];
                    
                    // Preserve previous login info for homepage display
                    if (!empty($prevTime) || !empty($prevStatus)) {
                        $_SESSION['showLoginNotice'] = [
                            'time'   => $prevTime,
                            'status' => $prevStatus
                        ];
                    }

                    // Ensure session is written before redirect
                    session_write_close();

                    // Redirect by role
                    if ($user['Role'] === 'Admin' || $user['Role'] === 'Staff') {
                        header("Location: ADMIN_Dashboard.php");
                        exit();
                    } else {
                        header("Location: HOME_Homepage.php");
                        exit();
                    }
                } else {
                    // Failed login increment failed attempts and record attempt
                    if ($user) {
                        $newAttempts = $failedAttempts + 1;

                        // Progressive lock
                        $maxAttempts = 5;          // threshold before lockout
                        $baseLockout = 300;        // 5 minutes in seconds
                        $lockoutCap  = 86400;      // 1 day
                        $nowTime     = time();
                        $lockoutTime = strtotime($lockoutUntil);

                        if ($newAttempts >= $maxAttempts) {
                            // Always increment attempts, even if locked
                            if ($lockoutUntil && $lockoutTime > $nowTime) {
                                $newAttempts = $failedAttempts + 1; // keep counting attempts during lockout
                                $startTime = $lockoutTime; // extend from current lockout
                            } else {
                                $startTime = $nowTime; // start new lockout
                            }

                            // Progressive: each attempt after threshold doubles time, capped at 1 day
                            $lockoutMultiplier = $newAttempts - $maxAttempts + 1; 
                            $lockoutDuration   = min($baseLockout * pow(2, $lockoutMultiplier - 1), $lockoutCap);

                            $newLockout = date("Y-m-d H:i:s", $startTime + $lockoutDuration);

                            $upd = $conn->prepare("UPDATE USERS 
                                                  SET FailedAttempts = ?, LockoutUntil = ?, LastLoginAttempt = ?, LastLoginIP = ?, LastLoginStatus = 'unsuccessful' 
                                                  WHERE Email = ?");
                            $upd->bind_param("issss", $newAttempts, $newLockout, $now, $ip, $email_prefill);

                            // LOGGING: account locked
                            $logger->logAuthFailure("Account locked until $newLockout after $newAttempts failed attempts", $email_prefill);
                        } else {
                            // Just update failed attempts without locking
                            $upd = $conn->prepare("UPDATE USERS SET FailedAttempts = ?, LastLoginAttempt = ?, LastLoginIP = ?, LastLoginStatus = 'unsuccessful' WHERE Email = ?");
                            $upd->bind_param("isss", $newAttempts, $now, $ip, $email_prefill);
                        }
                        $upd->execute();
                        $upd->close();

                        // LOGGING invalid attempt
                        $logger->logAuthFailure("Invalid password attempt: ($newAttempts/$maxAttempts).", $email_prefill);
                    } else {
                        // Dummy verify to even out timing
                        password_verify($password, password_hash("fake-password", PASSWORD_DEFAULT));
                        $logger->logAuthFailure("Login attempt with non-existent email", $email_prefill);
                    }
                    $error_message = "❌ Invalid username and/or password.";
                }
            }

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
        <a href="forgot_password.php">Forgot Password?</a>
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
