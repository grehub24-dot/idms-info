<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

try {
    $pdo = db();
    $pdo->query('SELECT 1');
    json_response(['ok' => true, 'service' => 'api', 'db' => 'connected']);
} catch (Throwable $e) {
    json_response(['ok' => false, 'service' => 'api', 'db' => 'error', 'error' => 'Database connection failed'], 500);
}
