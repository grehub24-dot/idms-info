<?php
require_once 'includes/db.php';

// Create a default admin if none exists
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
$stmt->execute();
if ($stmt->fetchColumn() == 0) {
    $email = 'admin@infotess.org';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'admin')");
    $stmt->execute([$email, $password]);
    echo "Default Admin created. Email: $email, Pass: admin123";
} else {
    echo "Admin already exists.";
}
?>
