<?php
// GLH shared header — include at the top of every page
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/csrf.php';

$cartCount = 0;
if (isLoggedIn() && ($_SESSION['role'] ?? '') === 'customer') {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/functions.php';
    $cartCount = getCartCount($pdo, $_SESSION['user_id']);
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Greenfield Local Hub — fresh, locally produced food and drink direct from our community of farmers and producers.">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' : '' ?>Greenfield Local Hub</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/dark-mode.css" media="(prefers-color-scheme: dark)">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Merriweather:wght@700&display=swap" rel="stylesheet">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>

<header class="site-header" role="banner">
    <div class="container header-inner">
        <a href="/index.php" class="logo" aria-label="Greenfield Local Hub home">
            <span class="logo-leaf" aria-hidden="true">🌿</span>
            <span class="logo-text">Greenfield <strong>Local Hub</strong></span>
        </a>

        <button class="nav-toggle" aria-controls="primary-nav" aria-expanded="false" aria-label="Toggle navigation menu">
            <span class="burger" aria-hidden="true"></span>
        </button>

        <nav id="primary-nav" class="primary-nav" aria-label="Primary navigation">
            <ul class="nav-list" role="list">
                <li><a href="/index.php" <?= $currentPage === 'index.php' ? 'aria-current="page"' : '' ?>>Home</a></li>
                <li><a href="/customer/products.php" <?= $currentPage === 'products.php' ? 'aria-current="page"' : '' ?>>Shop</a></li>
                <li><a href="/index.php#producers">Our Producers</a></li>
                <li><a href="/index.php#about">About GLH</a></li>
                <?php if (isLoggedIn()): ?>
                    <?php if (currentRole() === 'customer'): ?>
                        <li><a href="/customer/dashboard.php">My Account</a></li>
                        <li>
                            <a href="/customer/cart.php" class="cart-link" aria-label="Cart (<?= $cartCount ?> item<?= $cartCount !== 1 ? 's' : '' ?>)">
                                🛒 Cart <span class="cart-badge" aria-hidden="true"><?= $cartCount ?></span>
                            </a>
                        </li>
                    <?php elseif (currentRole() === 'producer'): ?>
                        <li><a href="/producer/dashboard.php">Producer Dashboard</a></li>
                    <?php elseif (currentRole() === 'admin'): ?>
                        <li><a href="/admin/dashboard.php">Admin</a></li>
                    <?php endif; ?>
                    <li><a href="/logout.php">Log Out</a></li>
                <?php else: ?>
                    <li><a href="/login.php" class="btn btn-outline">Log In</a></li>
                    <li><a href="/register.php" class="btn btn-primary">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<main id="main-content" tabindex="-1">
