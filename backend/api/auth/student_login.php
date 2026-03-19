<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$body = read_json_body();
$indexNumber = isset($body['index_number']) ? trim((string)$body['index_number']) : '';
$password = isset($body['password']) ? (string)$body['password'] : '';

if ($indexNumber === '' || $password === '') {
    json_response(['error' => 'Index number and password are required'], 400);
}

try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT student_id, full_name, index_number, password FROM students WHERE index_number = ? LIMIT 1');
    $stmt->execute([$indexNumber]);
    $student = $stmt->fetch();

    if (!$student || !password_verify($password, $student['password'])) {
        json_response(['error' => 'Invalid credentials'], 401);
    }

    session_regenerate_id(true);
    $_SESSION['actor_type'] = 'student';
    $_SESSION['student_id'] = (int)$student['student_id'];

    json_response([
        'ok' => true,
        'user' => [
            'type' => 'student',
            'student_id' => (int)$student['student_id'],
            'full_name' => (string)$student['full_name'],
            'index_number' => (string)$student['index_number'],
        ],
    ]);
} catch (Throwable $e) {
    json_response(['error' => 'Login failed'], 500);
}
