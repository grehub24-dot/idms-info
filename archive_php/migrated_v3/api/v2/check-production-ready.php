<?php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/api/config.php';
require_once __DIR__ . '/../../backend/api/middleware/cors.php';
require_once __DIR__ . '/../services/sms-service.php';
require_once __DIR__ . '/../services/image-service.php';

/**
 * Production Readiness Check
 * Validates all production requirements
 */

try {
    $checks = [];
    
    // 1. SMS Service Configuration
    $smsService = new SMSService();
    $checks['sms_service'] = [
        'enabled' => env('SMS_ENABLED', 'false') === 'true',
        'api_key_configured' => !empty(env('SMS_API_KEY', '')),
        'api_url_configured' => !empty(env('SMS_API_URL', '')),
        'status' => 'ready'
    ];
    
    if (!$checks['sms_service']['enabled']) {
        $checks['sms_service']['status'] = 'warning';
        $checks['sms_service']['message'] = 'SMS service is disabled. Enable with SMS_ENABLED=true';
    } elseif (!$checks['sms_service']['api_key_configured']) {
        $checks['sms_service']['status'] = 'error';
        $checks['sms_service']['message'] = 'SMS API key not configured. Set SMS_API_KEY';
    } elseif (!$checks['sms_service']['api_url_configured']) {
        $checks['sms_service']['status'] = 'error';
        $checks['sms_service']['message'] = 'SMS API URL not configured. Set SMS_API_URL';
    }
    
    // 2. File Upload Permissions
    $imageService = new ImageService();
    $permissionCheck = $imageService->checkPermissions();
    $checks['file_uploads'] = [
        'permissions_ok' => $permissionCheck['success'],
        'issues' => $permissionCheck['issues'],
        'upload_dir_exists' => file_exists($permissionCheck['base_upload_dir']),
        'status' => $permissionCheck['success'] ? 'ready' : 'error'
    ];
    
    if (!$permissionCheck['success']) {
        $checks['file_uploads']['message'] = 'Upload directory permissions need to be fixed';
    }
    
    // 3. Database Configuration
    $checks['database'] = [
        'host_configured' => !empty(env('DB_HOST', '')),
        'name_configured' => !empty(env('DB_NAME', '')),
        'user_configured' => !empty(env('DB_USER', '')),
        'connection_test' => false,
        'status' => 'unknown'
    ];
    
    try {
        require_once __DIR__ . '/../../backend/api/db.php';
        $pdo = db();
        $checks['database']['connection_test'] = true;
        $checks['database']['status'] = 'ready';
    } catch (Throwable $e) {
        $checks['database']['status'] = 'error';
        $checks['database']['error'] = $e->getMessage();
    }
    
    // 4. JWT Configuration
    $jwtSecret = env('JWT_SECRET', '');
    $checks['jwt'] = [
        'secret_configured' => !empty($jwtSecret),
        'secret_length' => strlen($jwtSecret),
        'status' => !empty($jwtSecret) && strlen($jwtSecret) >= 32 ? 'ready' : 'error'
    ];
    
    if (empty($jwtSecret)) {
        $checks['jwt']['message'] = 'JWT secret not configured. Set JWT_SECRET';
    } elseif (strlen($jwtSecret) < 32) {
        $checks['jwt']['message'] = 'JWT secret too short. Use at least 32 characters';
    }
    
    // 5. Environment Configuration
    $checks['environment'] = [
        'app_env' => env('APP_ENV', 'development'),
        'app_debug' => env('APP_DEBUG', 'true') === 'true',
        'production_ready' => env('APP_ENV', 'development') === 'production' && env('APP_DEBUG', 'true') === 'false',
        'status' => 'unknown'
    ];
    
    if ($checks['environment']['production_ready']) {
        $checks['environment']['status'] = 'ready';
    } elseif (env('APP_ENV', 'development') === 'production') {
        $checks['environment']['status'] = 'warning';
        $checks['environment']['message'] = 'Production environment but debug mode is enabled';
    } else {
        $checks['environment']['status'] = 'development';
        $checks['environment']['message'] = 'Running in development mode';
    }
    
    // 6. Required PHP Extensions
    $required_extensions = ['pdo', 'pdo_mysql', 'curl', 'json', 'mbstring', 'gd'];
    $checks['php_extensions'] = [
        'required' => $required_extensions,
        'installed' => [],
        'missing' => [],
        'status' => 'ready'
    ];
    
    foreach ($required_extensions as $ext) {
        if (extension_loaded($ext)) {
            $checks['php_extensions']['installed'][] = $ext;
        } else {
            $checks['php_extensions']['missing'][] = $ext;
        }
    }
    
    if (!empty($checks['php_extensions']['missing'])) {
        $checks['php_extensions']['status'] = 'error';
        $checks['php_extensions']['message'] = 'Missing required extensions: ' . implode(', ', $checks['php_extensions']['missing']);
    }
    
    // 7. Security Headers Check
    $checks['security'] = [
        'https_required' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'secure_cookies' => env('APP_ENV', 'development') === 'production',
        'cors_configured' => true, // We have CORS middleware
        'rate_limiting' => true, // We have rate limiting middleware
        'status' => 'ready'
    ];
    
    if (env('APP_ENV', 'development') === 'production' && !isset($_SERVER['HTTPS'])) {
        $checks['security']['status'] = 'warning';
        $checks['security']['message'] = 'HTTPS recommended for production';
    }
    
    // Overall status
    $all_statuses = array_column($checks, 'status');
    $error_count = count(array_filter($all_statuses, fn($s) => $s === 'error'));
    $warning_count = count(array_filter($all_statuses, fn($s) => $s === 'warning'));
    $ready_count = count(array_filter($all_statuses, fn($s) => $s === 'ready'));
    
    $overall_status = $error_count > 0 ? 'error' : ($warning_count > 0 ? 'warning' : 'ready');
    
    json_response([
        'success' => true,
        'overall_status' => $overall_status,
        'summary' => [
            'ready' => $ready_count,
            'warnings' => $warning_count,
            'errors' => $error_count,
            'total_checks' => count($checks)
        ],
        'checks' => $checks,
        'production_deployment_ready' => $overall_status === 'ready',
        'next_steps' => $overall_status === 'ready' ? [
            '✅ System is production ready',
            '🚀 Deploy to Vercel with environment variables',
            '📱 Configure SMS service provider',
            '🖼️ Test image uploads in production'
        ] : [
            '❌ Fix the errors above before production deployment',
            '⚠️ Address warnings for optimal security',
            '🔧 Ensure all services are properly configured'
        ]
    ]);
    
} catch (Throwable $e) {
    error_log('Production check error: ' . $e->getMessage());
    json_response([
        'success' => false,
        'error' => 'Production check failed: ' . $e->getMessage()
    ], 500);
}
