<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$body = read_json_body();
$email = isset($body['email']) ? trim((string)$body['email']) : '';
$password = isset($body['password']) ? (string)$body['password'] : '';

if ($email === '' || $password === '') {
    json_response(['error' => 'Email and password are required'], 400);
}

try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT admin_id, name, email, password, role FROM admins WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password'])) {
        json_response(['error' => 'Invalid credentials'], 401);
    }

    session_regenerate_id(true);
    $_SESSION['actor_type'] = 'admin';
    $_SESSION['admin_id'] = (int)$admin['admin_id'];
    $_SESSION['role'] = (string)$admin['role'];

    json_response([
        'ok' => true,
        'user' => [
            'type' => 'admin',
            'admin_id' => (int)$admin['admin_id'],
            'name' => (string)$admin['name'],
            'email' => (string)$admin['email'],
            'role' => (string)$admin['role'],
        ],
    ]);
} catch (Throwable $e) {
    json_response(['error' => 'Login failed'], 500);
}
