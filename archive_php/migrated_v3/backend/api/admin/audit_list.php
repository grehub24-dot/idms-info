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

try {
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT audit_id, actor_admin_id, action, entity, entity_id, old_values, new_values, created_at\n'
        . 'FROM audit_logs\n'
        . 'ORDER BY audit_id DESC\n'
        . 'LIMIT ?'
    );
    $stmt->execute([$limit]);

    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
        if (isset($r['old_values']) && $r['old_values'] !== null) {
            $decoded = json_decode((string)$r['old_values'], true);
            $r['old_values'] = $decoded;
        }
        if (isset($r['new_values']) && $r['new_values'] !== null) {
            $decoded = json_decode((string)$r['new_values'], true);
            $r['new_values'] = $decoded;
        }
    }

    json_response(['ok' => true, 'audit_logs' => $rows]);
} catch (Throwable $e) {
    json_response(['error' => 'Failed to fetch audit logs'], 500);
}
