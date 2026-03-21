<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/api/config.php';
require_once __DIR__ . '/../../../backend/api/middleware/cors.php';

// Get raw request data
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true);

json_response([
    'success' => true,
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
    'raw_input' => $rawInput,
    'parsed_json' => $jsonData,
    'fields_present' => $jsonData ? array_keys($jsonData) : [],
    'temp_password_value' => $jsonData['temp_password'] ?? 'NOT_SET',
    'new_password_value' => $jsonData['new_password'] ?? 'NOT_SET',
    'email_value' => $jsonData['email'] ?? 'NOT_SET'
]);
