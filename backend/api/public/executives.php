<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

try {
    $stmt = $pdo->query("SELECT * FROM executives ORDER BY id ASC");
    $executives = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($executives)) {
        $executives = [
            [
                'full_name' => 'John Doe',
                'position' => 'President',
                'image_url' => 'images/user-placeholder.png',
                'bio' => 'Passionate about leadership and tech.',
                'email' => 'president@infotess.org'
            ],
            [
                'full_name' => 'Jane Smith',
                'position' => 'Vice President',
                'image_url' => 'images/user-placeholder.png',
                'bio' => 'Dedicated to student welfare.',
                'email' => 'vp@infotess.org'
            ]
        ];
    }

    json_response(['ok' => true, 'executives' => $executives]);
} catch (Exception $e) {
    json_response(['error' => 'Failed to fetch executives'], 500);
}
