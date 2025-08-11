<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // DB connection
    $conn = new mysqli("localhost", "root", "", "dbadm");
    if ($conn->connect_error) {
        error_log("DB Connection failed: " . $conn->connect_error);
        exit("Service unavailable."); // Fail securely
    }

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error_message = "❌ Please fill in both email and password.";
    } else {
        $stmt = $conn->prepare("SELECT userID, Password, Role FROM USERS WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        $login_success = false;

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            //if ($password === $user['Password']) {
            if (password_verify($password, $user['Password'])) {
                $login_success = true;

                $_SESSION['userID'] = $user['userID'];
                $_SESSION['role']   = $user['Role'];

                // Role-based redirection
                switch ($user['Role']) {
                    case 'Admin':
                        header("Location: ADMIN_Dashboard.php");
                        break;
                    case 'Staff':
                        header("Location: ADMIN_Dashboard.php");
                        break;
                    case 'Customer':
                        header("Location: HOME_Homepage.php");
                        break;
                    default:
                        exit("Access denied.");
                }
                exit();
            }
        }

        // Requirement #4: Generic message for any failure
        if (!$login_success) {
            $error_message = "❌ Invalid username and/or password.";
        }

        $stmt->close();
    }

    $conn->close();
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
