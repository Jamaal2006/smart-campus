<?php
// Session management for GLH

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

/**
 * Check whether the current user is authenticated.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Require authentication — redirect to login if not logged in.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * Require a specific role — redirect to home if the user does not have it.
 */
function requireRole(string $role): void
{
    requireLogin();
    if (($_SESSION['role'] ?? '') !== $role) {
        header('Location: /index.php?error=access_denied');
        exit;
    }
}

/**
 * Return the current user's role, or null if not logged in.
 */
function currentRole(): ?string
{
    return $_SESSION['role'] ?? null;
}// Session management functions

session_start();

function checkSession() {
    if(!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}