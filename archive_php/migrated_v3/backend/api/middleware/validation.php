<?php

declare(strict_types=1);

function validate_required(array $data, array $fields): array {
    $errors = [];
    
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    
    return $errors;
}

function validate_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_password_strength(string $password): array {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    return $errors;
}

function sanitize_string(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function sanitize_int($input): ?int {
    if ($input === null || $input === '') {
        return null;
    }
    
    $filtered = filter_var($input, FILTER_VALIDATE_INT);
    return $filtered === false ? null : $filtered;
}

function validate_pagination(array $params): array {
    $page = max(1, (int)($params['page'] ?? 1));
    $limit = min(100, max(1, (int)($params['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    return [
        'page' => $page,
        'limit' => $limit,
        'offset' => $offset
    ];
}

function validation_response(array $errors): void {
    json_response([
        'error' => 'Validation failed',
        'details' => $errors
    ], 422);
}
