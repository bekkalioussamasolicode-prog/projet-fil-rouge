<?php
// =========================================================================
// login.php - LOGIN PAGE
// =========================================================================
// This page is standalone (no sidebar layout).
// It handles both showing the form AND processing the form submission.
// =========================================================================

require_once 'backend/database.php';
require_once 'backend/auth.php';

// If already logged in, skip to dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

// Process the form when submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        require_once 'backend/functions.php';
        $user = getUserByEmail($pdo, $email);

        if ($user && password_verify($password, $user['password_hash'])) {
            loginUser($user);
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid email address or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Invoxa</title>
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
    <h2>Welcome back</h2>
    <p class="intro">Please enter your details to sign in.</p>

    <?php if (!empty($error)): ?>
        <div class="alert-box alert-box-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['registered'])): ?>
        <div class="alert-box alert-box-success">
            <i class="fa-solid fa-circle-check"></i>
            <span>Registration successful! Please login below.</span>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
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
        <div class="form-options">
            <label class="remember-me"><input type="checkbox" name="remember"><span>Remember me</span></label>
            <a href="#" class="forgot-password-link">Forgot password?</a>
        </div>
        <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:14px;">Login</button>
    </form>
    <div class="auth-footer-text">
        <span>Don't have an account?</span>
        <a href="register.php">Sign up</a>
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
