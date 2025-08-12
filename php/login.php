login.php
<?php
session_start();

// Default vars
$error_message = '';
$email_prefill = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_prefill = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email_prefill === '' || $password === '') {
        $error_message = "❌ Please fill in both email and password.";
    } else {
        $conn = new mysqli("localhost", "root", "", "dbadm");
        if ($conn->connect_error) {
            error_log("DB Connection failed: " . $conn->connect_error);
            $error_message = "Service temporarily unavailable. Please try again later.";
        } else {
            // Fetch user with login history
            $stmt = $conn->prepare("
                SELECT userID, Password, Role, FailedAttempts, LockoutUntil,
                       LastLogin, LastLoginIP, LastFailedLogin, LastFailedIP
                FROM USERS
                WHERE Email = ?
            ");
            $stmt->bind_param("s", $email_prefill);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            $failedAttempts = $user['FailedAttempts'] ?? 0;
            $lockoutUntil = $user['LockoutUntil'] ?? null;

            // Lockout check
            if ($user && $lockoutUntil && strtotime($lockoutUntil) > time()) {
                $error_message = "❌ Account locked due to multiple failed login attempts. Try again later.";
            } else {
                if ($user && password_verify($password, $user['Password'])) {
                    // Store old login details in session
                    $_SESSION['prevLastLogin'] = $user['LastLogin'];
                    $_SESSION['prevLastLoginIP'] = $user['LastLoginIP'];
                    $_SESSION['prevFailedLogin'] = $user['LastFailedLogin'];
                    $_SESSION['prevFailedLoginIP'] = $user['LastFailedIP'];

                    // Update current login time/IP & reset attempts
                    $currentIP = $_SERVER['REMOTE_ADDR'];
                    $upd = $conn->prepare("
                        UPDATE USERS
                        SET FailedAttempts = 0,
                            LockoutUntil = NULL,
                            LastLogin = NOW(),
                            LastLoginIP = ?
                        WHERE Email = ?
                    ");
                    $upd->bind_param("ss", $currentIP, $email_prefill);
                    $upd->execute();
                    $upd->close();

                    // Set session vars
                    $_SESSION['userID'] = $user['userID'];
                    $_SESSION['role']   = $user['Role'];

                    // Redirect by role
                    if (in_array($user['Role'], ['Admin', 'Staff'])) {
                        header("Location: ADMIN_Dashboard.php");
                    } else {
                        header("Location: HOME_Homepage.php");
                    }
                    exit();
                } else {
                    // Failed login attempt
                    if ($user) {
                        $newAttempts = $failedAttempts + 1;
                        $currentIP = $_SERVER['REMOTE_ADDR'];

                        // Update failed login time/IP
                        $failUpd = $conn->prepare("
                            UPDATE USERS
                            SET FailedAttempts = ?,
                                LastFailedLogin = NOW(),
                                LastFailedIP = ?
                                " . ($newAttempts >= 5 ? ", LockoutUntil = ?" : "") . "
                            WHERE Email = ?
                        ");

                        if ($newAttempts >= 5) {
                            $lockoutTime = date("Y-m-d H:i:s", time() + 60);
                            $failUpd->bind_param("isss", $newAttempts, $currentIP, $lockoutTime, $email_prefill);
                        } else {
                            $failUpd->bind_param("iss", $newAttempts, $currentIP, $email_prefill);
                        }

                        $failUpd->execute();
                        $failUpd->close();
                    } else {
                        // Dummy hash check for non-existent users
                        password_verify($password, password_hash("fake-password", PASSWORD_DEFAULT));
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
