<?php

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'Direct API v2 test is working!',
    'timestamp' => date('c'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'path' => $_GET['path'] ?? 'none'
]);
