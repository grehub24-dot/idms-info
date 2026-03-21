<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isLoggedIn() || !isAdmin()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$index_number = isset($_GET['index_number']) ? trim((string)$_GET['index_number']) : '';

if ($index_number === '') {
    http_response_code(400);
    echo json_encode(['error' => 'index_number is required']);
    exit;
}

try {
    global $pdo;

    // Check if class_name and stream columns exist, if not handle it gracefully
    // We select specific columns
    $stmt = $pdo->prepare(
        'SELECT id as student_id, full_name, index_number, department, level, class_name, stream, phone_number, created_at, updated_at '
        . 'FROM students '
        . 'WHERE index_number = ? '
        . 'LIMIT 1'
    );
    
    // If class_name doesn't exist, this query might fail.
    // However, assuming schema is up to date.
    
    try {
        $stmt->execute([$index_number]);
    } catch (PDOException $e) {
        // Fallback if class_name doesn't exist
        $stmt = $pdo->prepare(
            'SELECT id as student_id, full_name, index_number, department, level, phone_number, created_at, updated_at '
            . 'FROM students '
            . 'WHERE index_number = ? '
            . 'LIMIT 1'
        );
        $stmt->execute([$index_number]);
    }

    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        http_response_code(404);
        echo json_encode(['error' => 'Student not found']);
        exit;
    }

    http_response_code(200);
    echo json_encode(['ok' => true, 'student' => $student]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch student: ' . $e->getMessage()]);
    exit;
}
