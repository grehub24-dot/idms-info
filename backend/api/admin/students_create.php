<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

require_admin();

$body = read_json_body();

$fullName = isset($body['full_name']) ? trim((string)$body['full_name']) : '';
$indexNumber = isset($body['index_number']) ? trim((string)$body['index_number']) : '';
$department = isset($body['department']) ? trim((string)$body['department']) : '';
$level = isset($body['level']) ? trim((string)$body['level']) : '';
$email = isset($body['email']) ? trim((string)$body['email']) : null;
$phone = isset($body['phone']) ? trim((string)$body['phone']) : null;
$password = isset($body['password']) ? (string)$body['password'] : '';

if ($fullName === '' || $indexNumber === '' || $department === '' || $level === '' || $password === '') {
    json_response(['error' => 'full_name, index_number, department, level, password are required'], 400);
}

if ($email !== null && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['error' => 'Invalid email'], 400);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo = db();

    $stmt = $pdo->prepare(
        'INSERT INTO students (full_name, index_number, department, level, email, phone, password)\n'
        . 'VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    $stmt->execute([
        $fullName,
        $indexNumber,
        $department,
        $level,
        ($email === '') ? null : $email,
        ($phone === '') ? null : $phone,
        $hash,
    ]);

    json_response(['ok' => true, 'student_id' => (int)$pdo->lastInsertId()]);
} catch (Throwable $e) {
    json_response(['error' => 'Failed to create student'], 500);
}
