<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;

try {
    if ($limit > 0) {
        $stmt = $pdo->prepare("SELECT * FROM activities ORDER BY activity_date DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $pdo->query("SELECT * FROM activities ORDER BY activity_date DESC");
    }
    
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    json_response(['ok' => true, 'activities' => $activities]);
} catch (Exception $e) {
    json_response(['error' => 'Failed to fetch activities'], 500);
}
