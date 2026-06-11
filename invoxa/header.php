<?php

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
// $currentPage will be: 'dashboard', 'invoices', 'upload', 'profile', etc.
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoxa Management Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="frontend/css/style.css" rel="stylesheet">
</head>

<body>

    <div class="app-layout">

        <!-- SIDEBAR OVERLAY (mobile backdrop) -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header-row">
                <div class="brand-logo">
                    <div class="logo-box">i</div>
                    <div class="logo-text">
                        <h1>invoxa</h1><span>Management Pro</span>
                    </div>
                </div>
                <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close menu"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php" class="menu-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>"><i class="fa-solid fa-chart-simple menu-icon"></i><span>Dashboard</span></a></li>
                    <li><a href="invoices.php" class="menu-link <?= strpos($currentPage, 'invoice') === 0 ? 'active' : '' ?>"><i class="fa-regular fa-file-lines menu-icon"></i><span>Invoices</span></a></li>
                    <li><a href="upload.php" class="menu-link <?= $currentPage === 'upload' ? 'active' : '' ?>"><i class="fa-solid fa-cloud-arrow-up menu-icon"></i><span>Upload</span></a></li>
                    <li><a href="profile.php" class="menu-link <?= $currentPage === 'profile' ? 'active' : '' ?>"><i class="fa-regular fa-user menu-icon"></i><span>Profile</span></a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="menu-link logout-link"><i class="fa-solid fa-arrow-right-from-bracket menu-icon"></i><span>Logout</span></a>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <div class="main-wrapper">
            <header class="top-navbar">
                <button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
                <div class="search-bar">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" placeholder="Search invoices, clients..." id="global-search-input">
                </div>
                <div class="navbar-actions">
                    <button class="nav-btn" title="Notifications"><i class="fa-regular fa-bell"></i><span class="badge-dot"></span></button>
                    <a href="profile.php" class="nav-btn" title="Settings"><i class="fa-solid fa-gear"></i></a>
                    <div class="user-avatar-btn">
                        <span class="avatar-initials"><?= strtoupper(substr($_SESSION['user_first_name'] ?? 'U', 0, 1) . substr($_SESSION['user_last_name'] ?? 'S', 0, 1)) ?></span>
                        <span class="user-name-label"><?= htmlspecialchars($_SESSION['user_first_name'] ?? 'User') ?></span>
                    </div>
                </div>
            </header>
            <main class="page-content">