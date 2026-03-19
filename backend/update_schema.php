<?php
require_once 'includes/db.php';

try {
    $sql = "ALTER TABLE students ADD COLUMN class_name VARCHAR(50) AFTER level";
    $pdo->exec($sql);
    echo "Column class_name added successfully.";
} catch (PDOException $e) {
    echo "Error adding column: " . $e->getMessage();
}
?>