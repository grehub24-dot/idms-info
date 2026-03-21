<?php
// Vercel entrypoint for /api/v2/*
// Forwards to backend/api/v2/<path>

$path = isset($_GET['path']) ? (string)$_GET['path'] : '';
$path = ltrim($path, '/');

// Debug: Log the path for troubleshooting
error_log("API v2 Router - Path: " . $path);

// Basic path safety
if ($path === '' || str_contains($path, '..') || str_contains($path, "\\")) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid path']);
    exit;
}

$target = __DIR__ . '/../../backend/api/v2/' . $path;

// Debug: Log the target path
error_log("API v2 Router - Target: " . $target);

// If no file extension is provided, try adding .php
if (!pathinfo($target, PATHINFO_EXTENSION)) {
    $target .= '.php';
}

if (!is_file($target)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not found: ' . $path]);
    exit;
}

require $target;
