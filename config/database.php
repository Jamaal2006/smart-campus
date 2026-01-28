<?php
// Database connection configuration

$host = 'localhost'; // or your database host
$dbname = 'smart_campus';
$username = 'your_username'; // replace with your database username
$password = 'your_password'; // replace with your database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}