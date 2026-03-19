<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

require_admin();

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$limit = max(1, min(200, $limit));

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

try {
    $pdo = db();

    if ($q !== '') {
        $like = '%' . $q . '%';
        $stmt = $pdo->prepare(
            'SELECT student_id, full_name, index_number, department, level, email, phone, created_at, updated_at\n'
            . 'FROM students\n'
            . 'WHERE full_name LIKE ? OR index_number LIKE ? OR department LIKE ?\n'
            . 'ORDER BY student_id DESC\n'
            . 'LIMIT ?'
        );
        $stmt->execute([$like, $like, $like, $limit]);
    } else {
        $stmt = $pdo->prepare(
            'SELECT student_id, full_name, index_number, department, level, email, phone, created_at, updated_at\n'
            . 'FROM students\n'
            . 'ORDER BY student_id DESC\n'
            . 'LIMIT ?'
        );
        $stmt->execute([$limit]);
    }

    $rows = $stmt->fetchAll();
    json_response(['ok' => true, 'students' => $rows]);
} catch (Throwable $e) {
    json_response(['error' => 'Failed to fetch students'], 500);
}
