<?php
// logout.php - Logs the user out
require_once 'backend/database.php'; // starts session
require_once 'backend/auth.php';

logoutUser();
header("Location: login.php");
exit;
