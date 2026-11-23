<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Open Access Mode - No login required
$userRole = 'admin';
$userName = 'Vladimer';
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'Vladimer';
$_SESSION['role'] = 'admin';
$_SESSION['full_name'] = 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale Version 1</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/images/logo.png" alt="Kaimasan Restaurant Logo" class="pos-logo">
            <h2>KAIMASAN RESTAURANT</h2>
            <p>Point of Sale Version 1</p>
        </div>
        <nav>
            <ul>
                <li><a href="../index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>
                    <span class="icon"><i class="fa-solid fa-chart-pie"></i></span> Dashboard
                </a></li>
                <li><a href="pos.php" <?php echo basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'class="active"' : ''; ?>>
                    <span class="icon"><i class="fa-solid fa-cart-shopping"></i></span> Point of Sale
                </a></li>
                <li><a href="products.php" <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'class="active"' : ''; ?>>
                    <span class="icon"><i class="fa-solid fa-box"></i></span> Products
                </a></li>
                <li><a href="sales.php" <?php echo basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'class="active"' : ''; ?>>
                    <span class="icon"><i class="fa-solid fa-receipt"></i></span> Sales
                </a></li>
                <li><a href="users.php" <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'class="active"' : ''; ?>>
                    <span class="icon"><i class="fa-solid fa-users"></i></span> Users
                </a></li>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <div style="margin-bottom:8px; font-weight:600; color:#fff;">Vladimer</div>
            <div style="font-size:11px;">Admin (Open Access)</div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <div class="topbar">
            <h1><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
            <div style="display:flex; align-items:center; gap:12px;">
                <span class="user-badge">Admin</span>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>