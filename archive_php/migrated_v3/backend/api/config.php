<?php

declare(strict_types=1);

// Load .env file if it exists
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) {
            continue; // Skip comments
        }
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
            $_ENV[trim($key)] = trim($value);
        }
    }
}

function env(string $key, ?string $default = null): ?string {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return $value;
}

function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    
    // Ensure consistent response format
    if ($status >= 400) {
        $response = [
            'success' => false,
            'error' => is_string($data) ? $data : ($data['error'] ?? 'Unknown error'),
            'status' => $status
        ];
        if (isset($data['details'])) {
            $response['details'] = $data['details'];
        }
    } else {
        $response = [
            'success' => true,
            'data' => $data,
            'status' => $status
        ];
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        json_response(['error' => 'Invalid JSON body'], 400);
    }

    return $data;
}
