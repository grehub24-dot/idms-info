<?php
require_once 'includes/db.php';

try {
    $checkColumn = $pdo->query("SHOW COLUMNS FROM students LIKE 'stream'");
    if ($checkColumn->rowCount() == 0) {
        $pdo->exec("ALTER TABLE students ADD COLUMN stream VARCHAR(50) AFTER class_name");
        echo "Column 'stream' added successfully.";
    } else {
        echo "Column 'stream' already exists.";
    }
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>