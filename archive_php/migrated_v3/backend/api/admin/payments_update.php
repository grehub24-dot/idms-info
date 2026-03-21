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

$fields = [
    'amount' => array_key_exists('amount', $body) ? (float)$body['amount'] : null,
    'academic_year' => isset($body['academic_year']) ? trim((string)$body['academic_year']) : null,
    'semester' => isset($body['semester']) ? trim((string)$body['semester']) : null,
    'payment_date' => isset($body['payment_date']) ? trim((string)$body['payment_date']) : null,
    'payment_method' => isset($body['payment_method']) ? trim((string)$body['payment_method']) : null,
];

if ($fields['amount'] !== null && $fields['amount'] <= 0) {
    json_response(['error' => 'amount must be > 0'], 400);
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

    $set = [];
    $vals = [];

    foreach ($fields as $col => $val) {
        if ($val !== null) {
            $set[] = "$col = ?";
            $vals[] = $val;
        }
    }

    if (count($set) === 0) {
        json_response(['error' => 'No fields to update'], 400);
    }

    $vals[] = $paymentId;

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('UPDATE payments SET ' . implode(', ', $set) . ' WHERE payment_id = ?');
    $stmt->execute($vals);

    $stmt = $pdo->prepare(
        'SELECT p.payment_id, p.amount, p.academic_year, p.semester, p.payment_date, p.payment_method, p.receipt_number,\n'
        . '       s.student_id, s.full_name, s.index_number\n'
        . 'FROM payments p\n'
        . 'JOIN students s ON s.student_id = p.student_id\n'
        . 'WHERE p.payment_id = ?\n'
        . 'LIMIT 1'
    );
    $stmt->execute([$paymentId]);
    $new = $stmt->fetch();

    audit_log($pdo, $actorAdminId, 'update', 'payments', (string)$paymentId, $old, $new);

    $pdo->commit();

    json_response(['ok' => true, 'payment' => $new]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['error' => 'Failed to update payment'], 500);
}
