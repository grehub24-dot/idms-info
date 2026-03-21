<?php

declare(strict_types=1);

function rate_limit(int $requests = 60, int $window = 60): void {
    // Use client IP as identifier
    $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // For Vercel, we can use their rate limiting headers if available
    if (isset($_SERVER['HTTP_X_VERCEL_IP_COUNTRY'])) {
        $identifier = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $identifier;
    }
    
    // Simple in-memory rate limiting (for production, use Redis)
    static $rate_store = [];
    $key = $identifier . ':' . floor(time() / $window);
    
    if (!isset($rate_store[$key])) {
        $rate_store[$key] = 0;
    }
    
    $rate_store[$key]++;
    
    if ($rate_store[$key] > $requests) {
        header('X-RateLimit-Limit: ' . $requests);
        header('X-RateLimit-Remaining: 0');
        header('X-RateLimit-Reset: ' . (time() + $window));
        
        json_response([
            'error' => 'Too many requests',
            'details' => "Rate limit exceeded. Maximum {$requests} requests per {$window} seconds."
        ], 429);
    }
    
    $remaining = max(0, $requests - $rate_store[$key]);
    header('X-RateLimit-Limit: ' . $requests);
    header('X-RateLimit-Remaining: ' . $remaining);
    header('X-RateLimit-Reset: ' . (time() + $window));
}

function rate_limit_strict(int $requests = 10, int $window = 60): void {
    // Stricter rate limiting for sensitive endpoints like login
    rate_limit($requests, $window);
}
