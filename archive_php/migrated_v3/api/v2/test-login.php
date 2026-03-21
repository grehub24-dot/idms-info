<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/api/config.php';
require_once __DIR__ . '/../../../backend/api/middleware/cors.php';

json_response([
    'success' => true,
    'message' => 'Test your login with these credentials:',
    'test_users' => [
        [
            'email' => 'admin@infotess.org',
            'password' => 'Try your admin password',
            'role' => 'admin'
        ],
        [
            'email' => '1amanvid.da@gmail.com',
            'password' => 'Try your student password',
            'role' => 'student'
        ]
    ],
    'note' => 'These are existing users in your database. Use their actual passwords to test login.'
]);
