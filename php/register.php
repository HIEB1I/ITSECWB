<?php
session_start();
require_once 'db_connect.php';
require 'validation.php';

// Auto-generate userID
$sql = "SELECT userID FROM USERS ORDER BY userID DESC LIMIT 1";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
  $row = $result->fetch_assoc();
  $lastID = $row['userID'];
  $num = (int)substr($lastID, 1);
  $num++;
  $userID = 'U' . str_pad($num, 5, '0', STR_PAD_LEFT);
} else {
  $userID = 'U00001';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $firstName = trim($_POST['firstName']);
  $lastName  = trim($_POST['lastName']);
  $email     = trim($_POST['email']);

  // Enforce password complexity & length
  $passwordPlain = $_POST['password'] ?? '';
  $confirmPasswordPlain = $_POST['confirmPassword'] ?? '';

  // Check if passwords match
  if ($passwordPlain !== $confirmPasswordPlain) {
    die("❌ Passwords do not match.");
  }

  $minLength = 8;

  if (
    strlen($passwordPlain) < $minLength ||
    !preg_match('/[A-Z]/', $passwordPlain) ||  // Uppercase
    !preg_match('/[a-z]/', $passwordPlain) ||  // Lowercase
    !preg_match('/[0-9]/', $passwordPlain) ||  // Number
    !preg_match('/[\W]/', $passwordPlain)      // Special char
  ) {
    die("❌ Password must be at least $minLength characters long and include uppercase, lowercase, number, and special character.");
  }
  // Store strong salted hash using password_hash (built-in salt)

  // Hash the password (bcrypt with salt automatically handled)
  $password = password_hash($passwordPlain, PASSWORD_DEFAULT);

  // Security question + answer validation
  $security_question = trim($_POST['security_question']);
  $security_answer_plain = trim($_POST['security_answer']);

  $common_answers = [
    "dog", "cat", "blue", "pizza", "the bible", "1234", "password", "qwerty"
  ];

  if (
    strlen($security_answer_plain) < 6 ||
    in_array(strtolower($security_answer_plain), $common_answers) ||
    ctype_digit($security_answer_plain)
  ) {
    //("❌ Security answer is too easy to guess. Try something more unique.");
  }

  // Hash the security answer
  $security_answer_hash = password_hash($security_answer_plain, PASSWORD_DEFAULT);

  $role = 'Customer';

  // DATA VALIDATION: validation checks & compile
  $errors = [];

  if (!validateString($firstName, 1, 50)) {
    $errors[] = "First name must be between 1 and 50 characters.";
  }
  if (!validateString($lastName, 1, 50)) {
    $errors[] = "Last name must be between 1 and 50 characters.";
  }

  if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: register.php");
    exit;
  }
  $sql = "INSERT INTO USERS 
            (userID, FirstName, LastName, Password, Email, Role, SecurityQuestion, SecurityAnswerHash) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

  $stmt = $conn->prepare($sql);

  if (!$stmt) {
    die("SQL Error: " . $conn->error);
    header("Location: error_pages/general_error.php");
    exit();
  }

  $stmt->bind_param("ssssssss", $userID, $firstName, $lastName, $password, $email, $role, $security_question, $security_answer_hash);

  if ($stmt->execute()) {
    // LOGGING
    require_once 'security_logger.php';
    $logger = new SecurityLogger($conn);
    $logger->logEvent('APPLICATION_SUCCESS', "Account created successfully for user: $userID", $userID, $role);

    header("Location: login.php");
    exit();
  } else {
    error_log("Registration failed for email: $email");
    header("Location: /error_pages/general_error.php");
    exit();
  }

  $stmt->close();
  $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Create Account - Clothing Store</title>
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

    .register-container {
      max-width: 400px;
      margin: auto;
    }

    h2 {
      font-size: 22px;
      margin: 20px 0;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 12px;
      margin: 8px 0;
      box-sizing: border-box;
      border: 1px solid #000;
    }

    .create-btn {
      background-color: black;
      color: white;
      border: none;
      padding: 10px 30px;
      margin: 20px 0;
      cursor: pointer;
    }

    .footer-logos {
      display: flex;
      justify-content: space-around;
      align-items: center;
      flex-wrap: wrap;
      margin-top: 122px;
      padding: 20px;
      border-top: 1px solid #ccc;
    }

    .footer-logos img {
      height: 40px;
      max-width: 100px;
      margin: 10px;
      object-fit: contain;
    }

    a {
      color: black;
      text-decoration: none;
      font-size: 12px;
    }

    select {
      width: 100%;
      padding: 12px;
      margin: 8px 0;
      box-sizing: border-box;
      border: 1px solid #000;
      background-color: white;
      font-size: 14px;
      color: black;
      border-radius: 0;
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      background-image: url('data:image/svg+xml;utf8,<svg fill="black" height="14" viewBox="0 0 24 24" width="14" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>');
      background-repeat: no-repeat;
      background-position: right 10px center;
      background-size: 14px;
    }

    /* Password requirements styling */
    #password-requirements {
      list-style: none;
      padding-left: 0;
      font-size: 12px;
      text-align: left;
    }

    #password-requirements li {
      margin: 4px 0;
    }

    .valid {
      color: green;
    }

    .invalid {
      color: red;
    }

    #confirmMessage {
      font-size: 12px;
      color: red;
      display: none;
      margin-top: 4px;
      text-align: left;
    }
  </style>
</head>

<body>

  <a href="login.php" style="position: absolute; top: 20px; left: 50px; text-decoration: none; font-size: 30px;">←</a>

  <!-- KW Logo -->
  <div class="logo">
    <img src="../Logos/KW Logo.png" alt="KALYE WEST">
  </div>

  <hr>
  <!-- Signup Form -->
  <div class="register-container">
    <h2>Create account</h2>
    <form method="post" action="">

      <!-- DATA VALIDATION: display compiled errors -->
      <?php if (!empty($_SESSION['errors'])) : ?>
        <ul style="color:red;">
          <?php foreach ($_SESSION['errors'] as $error) echo "<li>$error</li>"; ?>
        </ul>
        <?php unset($_SESSION['errors']); ?>
      <?php endif; ?>

      <input type="text" name="firstName" placeholder="First Name" required>
      <input type="text" name="lastName" placeholder="Last Name" required>
      <input type="email" name="email" placeholder="Email Address" required>

      <input type="password" id="password" name="password" placeholder="Password" required>
      <ul id="password-requirements">
        <li id="req-length" class="invalid">❌ At least 8 characters</li>
        <li id="req-max" class="invalid">❌ No more than 120 characters</li>
        <li id="req-upper" class="invalid">❌ At least one uppercase letter</li>
        <li id="req-lower" class="invalid">❌ At least one lowercase letter</li>
        <li id="req-number" class="invalid">❌ At least one number (0–9)</li>
        <li id="req-special" class="invalid">❌ At least one special character (!@#$%^&*)</li>
      </ul>

      <!-- Confirm password field -->
      <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm Password" required>
      <div id="confirmMessage">❌ Passwords do not match</div>

      <select name="security_question" required>
        <option value="">-- Select a Security Question --</option>
        <option value="favorite_snack_child">What was your favorite snack or candy as a child?</option>
        <option value="elementary_school_name">What was the name of your elementary school?</option>
        <option value="favorite_board_game_child">What was the name of your favorite board game growing up?</option>
        <option value="favorite_singer">Who is your favorite singer?</option>
        <option value="favorite_tv_show">What is the name of your favorite TV show?</option>
        <option value="first_crush_fullname">What is the full name of the first person you had a crush on?</option>
      </select>

      <input type="text" name="security_answer" placeholder="Security Answer" required>

      <button class="create-btn" type="submit">Create</button>
    </form>
  </div>

  <div class="footer-logos">
    <img src="../Logos/Femme Logo.png" alt="FEMME">
    <img src="../Logos/Little Logo.png" alt="little human">
    <img src="../Logos/MNLA Logo.png" alt="MN+LA">
    <img src="../Logos/Sage Logo.png" alt="SageHill.">
    <img src="../Logos/Daily Logo.png" alt="Daily Flight">
  </div>

  <script>
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirmPassword');
    const confirmMessage = document.getElementById('confirmMessage');

    const requirements = {
      length: document.getElementById('req-length'),
      max: document.getElementById('req-max'),
      upper: document.getElementById('req-upper'),
      lower: document.getElementById('req-lower'),
      number: document.getElementById('req-number'),
      special: document.getElementById('req-special')
    };

    // Check password requirements
    passwordInput.addEventListener('input', function() {
      const value = passwordInput.value;

      requirements.length.className = value.length >= 8 ? 'valid' : 'invalid';
      requirements.max.className = value.length <= 120 ? 'valid' : 'invalid';
      requirements.upper.className = /[A-Z]/.test(value) ? 'valid' : 'invalid';
      requirements.lower.className = /[a-z]/.test(value) ? 'valid' : 'invalid';
      requirements.number.className = /\d/.test(value) ? 'valid' : 'invalid';
      requirements.special.className = /[!@#$%^&*]/.test(value) ? 'valid' : 'invalid';

      // Update icons
      for (let key in requirements) {
        if (requirements[key].className === 'valid') {
          requirements[key].textContent = '✅ ' + requirements[key].textContent.replace('❌ ', '').replace('✅ ', '');
        } else {
          requirements[key].textContent = '❌ ' + requirements[key].textContent.replace('✅ ', '').replace('❌ ', '');
        }
      }

      checkMatch();
    });

    // Check confirm password match
    confirmInput.addEventListener('input', checkMatch);

    function checkMatch() {
      if (confirmInput.value.length === 0) {
        confirmMessage.style.display = 'none';
        return;
      }
      if (confirmInput.value === passwordInput.value) {
        confirmMessage.style.display = 'none';
      } else {
        confirmMessage.style.display = 'block';
      }
    }
  </script>
</body>

</html>