<?php
// Vercel entrypoint for /api/*
// Forwards to backend/api/<path>

$path = isset($_GET['path']) ? (string)$_GET['path'] : '';
$path = ltrim($path, '/');

// Basic path safety
if ($path === '' || str_contains($path, '..') || str_contains($path, "\\")) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Invalid path']);
    exit;
}

$target = __DIR__ . '/../backend/api/' . $path;

if (!is_file($target)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Not found']);
    exit;
}

require $target;
