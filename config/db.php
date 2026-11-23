<?php
// Database configuration for Point of Sale Version 1
// Make sure you have installed XAMPP and MySQL is running
// Default XAMPP MySQL credentials: root / (empty password)

$host = 'localhost';
$dbname = 'pos_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<div style='padding:20px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;text-align:center;'>
        <strong>Database Connection Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>
        Make sure XAMPP Apache and MySQL are running, and you have imported database.sql
    </div>");
}
?>