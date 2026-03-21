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

$fields = [
    'full_name' => isset($body['full_name']) ? trim((string)$body['full_name']) : null,
    'index_number' => isset($body['index_number']) ? trim((string)$body['index_number']) : null,
    'department' => isset($body['department']) ? trim((string)$body['department']) : null,
    'level' => isset($body['level']) ? trim((string)$body['level']) : null,
    'email' => array_key_exists('email', $body) ? trim((string)$body['email']) : null,
    'phone' => array_key_exists('phone', $body) ? trim((string)$body['phone']) : null,
];

$password = isset($body['password']) ? (string)$body['password'] : null;

if ($fields['email'] !== null && $fields['email'] !== '' && !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
    json_response(['error' => 'Invalid email'], 400);
}

try {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT student_id, full_name, index_number, department, level, email, phone FROM students WHERE student_id = ? LIMIT 1');
    $stmt->execute([$studentId]);
    $old = $stmt->fetch();

    if (!$old) {
        json_response(['error' => 'Student not found'], 404);
    }

    $set = [];
    $vals = [];

    foreach ($fields as $col => $val) {
        if ($val !== null) {
            $set[] = "$col = ?";
            $vals[] = ($val === '') ? null : $val;
        }
    }

    if ($password !== null && $password !== '') {
        $set[] = 'password = ?';
        $vals[] = password_hash($password, PASSWORD_DEFAULT);
    }

    if (count($set) === 0) {
        json_response(['error' => 'No fields to update'], 400);
    }

    $vals[] = $studentId;

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('UPDATE students SET ' . implode(', ', $set) . ' WHERE student_id = ?');
    $stmt->execute($vals);

    $stmt = $pdo->prepare('SELECT student_id, full_name, index_number, department, level, email, phone FROM students WHERE student_id = ? LIMIT 1');
    $stmt->execute([$studentId]);
    $new = $stmt->fetch();

    audit_log($pdo, $actorAdminId, 'update', 'students', (string)$studentId, $old, $new);

    $pdo->commit();

    json_response(['ok' => true, 'student' => $new]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['error' => 'Failed to update student'], 500);
}
