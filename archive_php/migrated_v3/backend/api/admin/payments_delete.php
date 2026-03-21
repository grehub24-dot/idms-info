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
$paymentId = isset($body['payment_id']) ? (int)$body['payment_id'] : 0;

if ($paymentId <= 0) {
    json_response(['error' => 'payment_id is required'], 400);
}

try {
    $pdo = db();

    $stmt = $pdo->prepare(
        'SELECT p.payment_id, p.amount, p.academic_year, p.semester, p.payment_date, p.payment_method, p.receipt_number,\n'
        . '       s.student_id, s.full_name, s.index_number\n'
        . 'FROM payments p\n'
        . 'JOIN students s ON s.student_id = p.student_id\n'
        . 'WHERE p.payment_id = ?\n'
        . 'LIMIT 1'
    );
    $stmt->execute([$paymentId]);
    $old = $stmt->fetch();

    if (!$old) {
        json_response(['error' => 'Payment not found'], 404);
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('DELETE FROM payments WHERE payment_id = ?');
    $stmt->execute([$paymentId]);

    audit_log($pdo, $actorAdminId, 'delete', 'payments', (string)$paymentId, $old, null);

    $pdo->commit();

    json_response(['ok' => true]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['error' => 'Failed to delete payment'], 500);
}
