<?php

require_once __DIR__ . '/config/database.php';

$name = 'System Administrator';
$email = 'admin@dionisio.com';
$password = 'adminbj123';

$stmt = $pdo->prepare("
    INSERT INTO admins (full_name, email, password_hash)
    VALUES (?, ?, ?)
");

try {
    $stmt->execute([
        $name,
        $email,
        password_hash($password, PASSWORD_DEFAULT)
    ]);

    echo "Admin created successfully.";
} catch (PDOException $e) {
    echo "Admin already exists.";
}