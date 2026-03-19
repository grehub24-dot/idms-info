<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Destroy session
$_SESSION = [];
session_destroy();

json_response(['ok' => true, 'message' => 'Logged out successfully']);
