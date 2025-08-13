<?php
require_once 'db_connect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$step = 1;
$error_message = '';
$email_prefill = '';
$security_question = '';
$user_id = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // STEP 1: Check email and display security question
    if (isset($_POST['step']) && $_POST['step'] == 1) {
        $email_prefill = trim($_POST['email']);
        
        $stmt = $conn->prepare("SELECT userID, SecurityQuestion FROM USERS WHERE Email = ?");
        $stmt->bind_param("s", $email_prefill);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            $step = 2;
            $user_id = $user['userID'];

            $questions_map = [
                'favorite_snack_child' => 'What was your favorite snack or candy as a child?',
                'elementary_school_name' => 'What was the name of your elementary school?',
                'favorite_board_game_child' => 'What was the name of your favorite board game growing up?',
                'nickname' => 'What is the nickname only your family calls you?',
                'first_stuff_animal' => 'What was the name of your first stuffed animal?',
                'first_crush_fullname' => 'What is the full name of the first person you had a crush on?'
            ];

            $security_question = $questions_map[$user['SecurityQuestion']] ?? $user['SecurityQuestion'];

            $_SESSION['reset_user_id'] = $user_id;
            $_SESSION['reset_email'] = $email_prefill;
        } else {
            $error_message = "❌ No account found with that email."; 
        }
    }

    // STEP 2: Check answer, allow password reset
    elseif (isset($_POST['step']) && $_POST['step'] == 2) {
        $entered_answer = trim($_POST['security_answer']);
        $user_id = $_SESSION['reset_user_id'] ?? '';

        if ($user_id) {
            $stmt = $conn->prepare("SELECT SecurityAnswerHash FROM USERS WHERE userID = ?");
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($entered_answer, $user['SecurityAnswerHash'])) {
                $step = 3; // move to password reset
            } else {
                $error_message = "❌ Incorrect answer to the security question.";
                $step = 2;
                $security_question = ''; // hide the question if wrong for security
            }
        } else {
            $error_message = "❌ Session expired. Please start over."; // shows only if $_SESSION['reset_user_id'] is missing.
            $step = 1;
        }
    }

// STEP 3: Reset password
elseif (isset($_POST['step']) && $_POST['step'] == 3) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        $error_message = "❌ Passwords do not match.";
        $step = 3;
    } elseif (
        strlen($new_password) < 8 ||
        !preg_match('/[A-Z]/', $new_password) ||
        !preg_match('/[a-z]/', $new_password) ||
        !preg_match('/[0-9]/', $new_password) ||
        !preg_match('/[\W]/', $new_password)
    ) {
        $error_message = "❌ Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.";
        $step = 3;
    } else {
        $user_id = $_SESSION['reset_user_id'] ?? '';
        if ($user_id) {

            // Fetch current password and last change time
            $stmt = $conn->prepare("SELECT Password, LastPasswordChange FROM USERS WHERE userID = ?");
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $userData = $result->fetch_assoc();
            $stmt->close();

            if (!$userData) {
                $error_message = "❌ User not found.";
                $step = 1;
            } else {
                // Rule 2: Check if at least 1 day has passed since last password change
                if (!empty($userData['LastPasswordChange'])) {
                    $lastChange = strtotime($userData['LastPasswordChange']);
                    if ((time() - $lastChange) < 86400) { // 86400 seconds = 1 day
                        $error_message = "❌ You can only change your password once every 24 hours.";
                        $step = 3;
                        goto skip_reset;
                    }
                }

                // Rule 1: Prevent password re-use (check current and history)
                $reuseFound = false;

                // Check current password
                if (password_verify($new_password, $userData['Password'])) {
                    $reuseFound = true;
                }

                // Check password history table
                $histStmt = $conn->prepare("SELECT PasswordHash FROM USER_PASSWORD_HISTORY WHERE userID = ?");
                $histStmt->bind_param("s", $user_id);
                $histStmt->execute();
                $histResult = $histStmt->get_result();
                while ($row = $histResult->fetch_assoc()) {
                    if (password_verify($new_password, $row['PasswordHash'])) {
                        $reuseFound = true;
                        break;
                    }
                }
                $histStmt->close();

                if ($reuseFound) {
                    $error_message = "❌ You cannot reuse any of your previous passwords.";
                    $step = 3;
                    goto skip_reset;
                }

                // Save current password into history before updating
                $saveHist = $conn->prepare("INSERT INTO USER_PASSWORD_HISTORY (userID, PasswordHash) VALUES (?, ?)");
                $saveHist->bind_param("ss", $user_id, $userData['Password']);
                $saveHist->execute();
                $saveHist->close();

                // Update new password and timestamp
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE USERS SET Password = ?, LastPasswordChange = NOW() WHERE userID = ?");
                $updateStmt->bind_param("ss", $hashed_password, $user_id);
                $updateStmt->execute();
                $updateStmt->close();

                // Clear session data for reset
                unset($_SESSION['reset_user_id'], $_SESSION['reset_email']);

                header("Location: login.php?reset=success");
                exit();
            }
        } else {
            $error_message = "❌ Session expired. Please start over.";
            $step = 1;
        }
    }
}
    skip_reset:
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; background-color: white; margin: 0; padding: 0; }
        .container { max-width: 400px; margin: auto; padding: 20px; }
        input, select { width: 100%; padding: 10px; margin: 8px 0; box-sizing: border-box; }
        button { background-color: black; color: white; border: none; padding: 10px 30px; cursor: pointer; }
        .error { color: red; margin-top: 10px; }

        /* Password checklist styles (used only in step 3) */
        #password-requirements { list-style: none; padding-left: 0; font-size: 12px; text-align: left; margin: 8px 0 16px 0; }
        #password-requirements li { margin: 4px 0; }
        .valid { color: green; }
        .invalid { color: red; }

        #confirmMessage { font-size: 12px; color: red; display: none; margin-top: 4px; text-align: left; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>

        <?php if ($error_message): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <form method="post">
                <input type="hidden" name="step" value="1">
                <input type="email" name="email" placeholder="Enter your email" required>
                <button type="submit">Next</button>
            </form>

        <?php elseif ($step == 2): ?>
            <form method="post">
                <input type="hidden" name="step" value="2">
                <p><strong>Security Question:</strong></p>
                <p><?php echo htmlspecialchars($security_question); ?></p>
                <input type="text" name="security_answer" placeholder="Enter your answer" required>
                <button type="submit">Next</button>
            </form>

        <?php elseif ($step == 3): ?>
            <form method="post">
                <input type="hidden" name="step" value="3">

                <!-- NEW PASSWORD (id matches the JS) -->
                <input type="password" id="password" name="new_password" placeholder="New Password" required>

                <!-- checklist -->
                <ul id="password-requirements">
                    <li id="req-length" class="invalid">❌ At least 8 characters</li>
                    <li id="req-max" class="invalid">❌ No more than 120 characters</li>
                    <li id="req-upper" class="invalid">❌ At least one uppercase letter</li>
                    <li id="req-lower" class="invalid">❌ At least one lowercase letter</li>
                    <li id="req-number" class="invalid">❌ At least one number (0–9)</li>
                    <li id="req-special" class="invalid">❌ At least one special character (!@#$%^&*)</li>
                </ul>

                <!-- CONFIRM -->
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm New Password" required>
                <div id="confirmMessage">❌ Passwords do not match</div>

                <button type="submit">Reset Password</button>
            </form>
        <?php endif; ?>

        <p><a href="login.php">Back to Login</a></p>
    </div>

    <script>
    // guard: run only if password input is present (i.e. step 3)
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        const requirements = {
            length: document.getElementById('req-length'),
            max: document.getElementById('req-max'),
            upper: document.getElementById('req-upper'),
            lower: document.getElementById('req-lower'),
            number: document.getElementById('req-number'),
            special: document.getElementById('req-special')
        };

        const confirmInput = document.getElementById('confirm_password');
        const confirmMessage = document.getElementById('confirmMessage');

        passwordInput.addEventListener('input', updateChecklist);
        if (confirmInput) {
            confirmInput.addEventListener('input', checkMatch);
        }

        function updateChecklist() {
            const value = passwordInput.value;

            if (value.length > 0) {
                requirements.length.className = value.length >= 8 ? 'valid' : 'invalid';
                requirements.max.className = value.length <= 120 ? 'valid' : 'invalid';
                requirements.upper.className = /[A-Z]/.test(value) ? 'valid' : 'invalid';
                requirements.lower.className = /[a-z]/.test(value) ? 'valid' : 'invalid';
                requirements.number.className = /\d/.test(value) ? 'valid' : 'invalid';
                requirements.special.className = /[!@#$%^&*]/.test(value) ? 'valid' : 'invalid';
            } else {
                for (let key in requirements) {
                    requirements[key].className = 'invalid';
                }
            }

            // update checkmarks text
            for (let key in requirements) {
                const el = requirements[key];
                if (el.className === 'valid') {
                    el.textContent = '✅ ' + el.textContent.replace('❌ ', '').replace('✅ ', '');
                } else {
                    el.textContent = '❌ ' + el.textContent.replace('✅ ', '').replace('❌ ', '');
                }
            }

            // also re-check confirm match (so confirm message updates while typing)
            checkMatch();
        }

        function checkMatch() {
            if (!confirmInput) return;
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
    }
    </script>
</body>
</html>
