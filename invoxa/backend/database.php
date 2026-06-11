<?php
// Start the session (needed on every page)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if db_config.php exists (created by setup.php)
$configPath = __DIR__ . '/db_config.php';
if (!file_exists($configPath)) {
    header("Location: setup.php");
    exit;
}

require_once $configPath;

// Connect to MySQL
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
