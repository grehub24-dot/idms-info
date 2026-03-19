<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$admin = require_admin();
$actorAdminId = (int)$admin['admin_id'];

$body = read_json_body();
$studentId = isset($body['student_id']) ? (int)$body['student_id'] : 0;

if ($studentId <= 0) {
    json_response(['error' => 'student_id is required'], 400);
}

try {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT student_id, full_name, index_number, department, level, email, phone FROM students WHERE student_id = ? LIMIT 1');
    $stmt->execute([$studentId]);
    $old = $stmt->fetch();

    if (!$old) {
        json_response(['error' => 'Student not found'], 404);
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('DELETE FROM students WHERE student_id = ?');
    $stmt->execute([$studentId]);

    audit_log($pdo, $actorAdminId, 'delete', 'students', (string)$studentId, $old, null);

    $pdo->commit();

    json_response(['ok' => true]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['error' => 'Failed to delete student (may have linked payments)'], 500);
}
