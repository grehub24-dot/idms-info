<?php
require_once 'includes/db.php';

try {
    // Add profile_picture to students table
    $sql = "ALTER TABLE students ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL";
    $pdo->exec($sql);
    echo "Column profile_picture added successfully to students table.<br>";
} catch (PDOException $e) {
    echo "Error adding profile_picture: " . $e->getMessage() . "<br>";
}

try {
    // Add is_password_reset to users table
    // Default 0 means password has NOT been reset (i.e. it's the auto-generated one)
    $sql = "ALTER TABLE users ADD COLUMN is_password_reset TINYINT(1) DEFAULT 0";
    $pdo->exec($sql);
    echo "Column is_password_reset added successfully to users table.<br>";
} catch (PDOException $e) {
    echo "Error adding is_password_reset: " . $e->getMessage() . "<br>";
}
?>