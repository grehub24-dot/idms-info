<?php
require_once 'includes/db.php';

try {
    // Create system_settings table
    $sql = "CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "Table system_settings created successfully.<br>";

    // Insert default settings if they don't exist
    $defaults = [
        'current_academic_year' => '2025/2026',
        'current_semester' => '1',
        'annual_dues_amount' => '100.00',
        'payment_modes' => 'Cash,Mobile Money,Bank Transfer',
        'department_name' => 'Information Technology Education',
        'institution_name' => 'AAMUSTED'
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($defaultSettings as $key => $value) {
        $stmt->execute([$key, $value]);
    }
    echo "Default settings initialized successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>