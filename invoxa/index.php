<?php
// index.php - Just redirects to dashboard or login
require_once 'backend/database.php';
require_once 'backend/auth.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit;
