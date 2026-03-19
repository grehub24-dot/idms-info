<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

function audit_log(PDO $pdo, int $actorAdminId, string $action, string $entity, ?string $entityId, $oldValues, $newValues): void {
    $stmt = $pdo->prepare(
        'INSERT INTO audit_logs (actor_admin_id, action, entity, entity_id, old_values, new_values)\n'
        . 'VALUES (?, ?, ?, ?, ?, ?)'
    );

    $oldJson = $oldValues === null ? null : json_encode($oldValues);
    $newJson = $newValues === null ? null : json_encode($newValues);

    $stmt->execute([$actorAdminId, $action, $entity, $entityId, $oldJson, $newJson]);
}
