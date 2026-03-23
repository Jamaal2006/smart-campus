<?php
// Database connection configuration for Greenfield Local Hub (GLH)

$host   = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'glh';
$dbuser = getenv('DB_USER') ?: 'glh_user';
$dbpass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $dbuser,
        $dbpass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // Log error securely — never expose raw details to the browser
    error_log('Database connection failed: ' . $e->getMessage());
    die('Service temporarily unavailable. Please try again later.');
}