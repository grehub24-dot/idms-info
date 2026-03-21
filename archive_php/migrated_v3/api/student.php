<?php
// Vercel entrypoint for /student/*
// Forwards to backend/student/<path>

$path = isset($_GET['path']) ? (string)$_GET['path'] : '';
$path = ltrim($path, '/');

if ($path === '' || str_contains($path, '..') || str_contains($path, "\\")) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'Invalid path';
    exit;
}

// Support extensionless URLs
if (!str_ends_with($path, '.php')) {
    $path .= '.php';
}

$target = __DIR__ . '/../backend/student/' . $path;

if (!is_file($target)) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo 'Not found';
    exit;
}

require $target;
