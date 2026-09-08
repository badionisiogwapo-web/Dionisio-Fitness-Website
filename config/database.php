<?php
$host = 'localhost';
$dbname = 'dionisio_fitness';
$username = 'root';
$password = '';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password, $options);
} catch (PDOException $e) {
    die('Database connection failed. Start MySQL and import database.sql in phpMyAdmin.');
}
