<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function generate_jwt(array $payload, string $secret, int $expiry = 3600): string {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload['exp'] = time() + $expiry;
    $payload = json_encode($payload);
    
    $header_encoded = base64url_encode($header);
    $payload_encoded = base64url_encode($payload);
    
    $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", $secret, true);
    $signature_encoded = base64url_encode($signature);
    
    return "$header_encoded.$payload_encoded.$signature_encoded";
}

function verify_jwt(string $jwt, string $secret): ?array {
    $token_parts = explode('.', $jwt);
    
    if (count($token_parts) !== 3) {
        return null;
    }
    
    $header = base64url_decode($token_parts[0]);
    $payload = base64url_decode($token_parts[1]);
    $signature = $token_parts[2];
    
    $payload_array = json_decode($payload, true);
    if (!$payload_array) {
        return null;
    }
    
    // Check expiration
    if (isset($payload_array['exp']) && $payload_array['exp'] < time()) {
        return null;
    }
    
    // Verify signature
    $expected_signature = hash_hmac('sha256', "$token_parts[0].$token_parts[1]", $secret, true);
    $expected_signature_encoded = base64url_encode($expected_signature);
    
    if (!hash_equals($signature, $expected_signature_encoded)) {
        return null;
    }
    
    return $payload_array;
}

function get_auth_user(): ?array {
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    
    if (!$auth_header || !str_starts_with($auth_header, 'Bearer ')) {
        return null;
    }
    
    $token = substr($auth_header, 7);
    $secret = env('JWT_SECRET', 'your-secret-key-change-this');
    
    return verify_jwt($token, $secret);
}

function require_auth(): array {
    $user = get_auth_user();
    
    if (!$user) {
        json_response(['error' => 'Unauthorized'], 401);
    }
    
    return $user;
}

function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}
