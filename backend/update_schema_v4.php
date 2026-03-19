<?php
require_once 'includes/db.php';

try {
    $checkColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_password_reset'");
    if ($checkColumn->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_password_reset TINYINT(1) DEFAULT 0");
        echo "Column is_password_reset added successfully.";
    } else {
        echo "Column already exists.";
    }
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>