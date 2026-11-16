<?php
// backend/config/db.php

$host = '127.0.0.1';
$dbname = 'joey_tahian';
$user = 'root';
$pass = ''; // Default XAMPP/LAMPP password is often empty

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Fetch associative arrays by default
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}
