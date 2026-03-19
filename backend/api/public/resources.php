<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

try {
    $stmt = $pdo->query("SELECT * FROM student_resources ORDER BY created_at DESC");
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    json_response(['ok' => true, 'resources' => $resources]);
} catch (Exception $e) {
    json_response(['error' => 'Failed to fetch resources'], 500);
}
