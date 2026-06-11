<?php
// =========================================================================
// register.php - REGISTRATION PAGE
// =========================================================================

require_once 'backend/database.php';
require_once 'backend/auth.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName       = trim($_POST['first_name'] ?? '');
    $lastName        = trim($_POST['last_name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        require_once 'backend/functions.php';
        if (getUserByEmail($pdo, $email)) {
            $error = "This email address is already registered.";
        } else {
            createUser($pdo, $firstName, $lastName, $email, $password);
            header("Location: login.php?registered=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Invoxa</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="frontend/css/style.css" rel="stylesheet">
</head>
<body class="auth-page">
<div class="auth-card">
    <div class="logo">
        <div class="logo-box">i</div>
        <div class="logo-text"><h1>invoxa</h1><span>Management Pro</span></div>
    </div>
    <h2>Create an Account</h2>
    <p class="intro">Enter your details to sign up.</p>

    <?php if (!empty($error)): ?>
        <div class="alert-box alert-box-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <div class="form-row" style="margin-bottom:0;">
            <div class="input-group">
                <label for="first_name">First Name</label>
                <div class="input-icon-wrapper">
                    <i class="fa-regular fa-user"></i>
                    <input type="text" id="first_name" name="first_name" class="form-control" placeholder="Alex" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                </div>
            </div>
            <div class="input-group">
                <label for="last_name">Last Name</label>
                <div class="input-icon-wrapper">
                    <i class="fa-regular fa-user"></i>
                    <input type="text" id="last_name" name="last_name" class="form-control" placeholder="Mercer" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                </div>
            </div>
        </div>
        <div class="input-group">
            <label for="email">Email Address</label>
            <div class="input-icon-wrapper">
                <i class="fa-regular fa-envelope"></i>
                <input type="email" id="email" name="email" class="form-control" placeholder="name@company.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
        </div>
        <div class="input-group">
            <label for="password">Password</label>
            <div class="input-icon-wrapper">
                <i class="fa-solid fa-lock"></i>
                <input type="password" id="password" name="password" class="form-control password-field" placeholder="--------" required>
                <i class="fa-regular fa-eye-slash toggle-password" onclick="togglePasswordVisibility('password', this)"></i>
            </div>
        </div>
        <div class="input-group">
            <label for="confirm_password">Confirm Password</label>
            <div class="input-icon-wrapper">
                <i class="fa-solid fa-lock"></i>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control password-field" placeholder="--------" required>
                <i class="fa-regular fa-eye-slash toggle-password" onclick="togglePasswordVisibility('confirm_password', this)"></i>
            </div>
        </div>
        <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:14px;margin-top:10px;">Sign Up</button>
    </form>
    <div class="auth-footer-text">
        <span>Already have an account?</span>
        <a href="login.php">Login</a>
    </div>
    <div class="auth-bottom-links">
        <a href="#">Privacy Policy</a><span>.</span><a href="#">Terms of Service</a>
    </div>
</div>
<script>
function togglePasswordVisibility(fieldId, iconElement) {
    const input = document.getElementById(fieldId);
    if (input.type === 'password') { input.type = 'text'; iconElement.classList.replace('fa-eye-slash','fa-eye'); }
    else { input.type = 'password'; iconElement.classList.replace('fa-eye','fa-eye-slash'); }
}
</script>
</body>
</html>
