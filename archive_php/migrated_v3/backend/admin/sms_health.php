<?php
require_once '../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn() || !isAdmin()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$defaultApiKey = '$2y$10$6oYYcjc6Ge3/W.P.1Yqk6eHBs0ERVFR6IaBQ2qpYGBnMYp28B3uPe';
$defaultUsername = 'amanvid';
$defaultSenderId = 'INFOTESS';
$envApiKey = getenv('WIGAL_API_KEY') ?: (getenv('SMS_API_KEY') ?: '');
$envUsername = getenv('WIGAL_USERNAME') ?: (getenv('SMS_USERNAME') ?: '');
$envSenderId = getenv('WIGAL_SENDER_ID') ?: (getenv('SMS_SENDER_ID') ?: '');
$apiKey = $envApiKey ?: $defaultApiKey;
$username = $envUsername ?: $defaultUsername;
$senderId = $envSenderId ?: $defaultSenderId;
$endpoint = getenv('WIGAL_SMS_ENDPOINT') ?: (getenv('SMS_API_URL') ?: 'https://frogapi.wigal.com.gh/api/v3/sms/send');
$disableSslVerify = getenv('SMS_DISABLE_SSL_VERIFY');
$isVercel = (getenv('VERCEL') === '1') || (getenv('NOW_REGION') !== false);
$host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : '';
$isLocalHost = stripos($host, 'localhost') !== false || stripos($host, '127.0.0.1') !== false;
$disableSslOnLocal = !$isVercel && ($disableSslVerify === false || $disableSslVerify === '') && $isLocalHost;
$sslVerifyDisabled = ($disableSslVerify === '1' || strtolower((string)$disableSslVerify) === 'true' || $disableSslOnLocal);
$smsLogFile = __DIR__ . '/../sms_logs/sms.log';
$lastLog = null;

if (file_exists($smsLogFile)) {
    $lines = @file($smsLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($lines) && !empty($lines)) {
        $lastLog = $lines[count($lines) - 1];
    }
}

$checks = [
    'api_key' => $apiKey !== '',
    'username' => $username !== '',
    'sender_id' => $senderId !== '',
    'endpoint' => $endpoint !== '',
    'curl_extension' => function_exists('curl_init'),
];

$ok = $checks['api_key'] && $checks['username'] && $checks['sender_id'] && $checks['endpoint'] && $checks['curl_extension'];
$warnings = [];
if ($envApiKey === '' || $envUsername === '' || $envSenderId === '') {
    $warnings[] = 'Using fallback SMS credentials. Set WIGAL_API_KEY, WIGAL_USERNAME and WIGAL_SENDER_ID.';
}
if ($lastLog !== null && stripos($lastLog, 'SSL certificate problem') !== false) {
    $warnings[] = 'Local SSL certificate issue detected. Use SMS_DISABLE_SSL_VERIFY=true for local development only.';
}

echo json_encode([
    'ok' => $ok,
    'checks' => $checks,
    'settings' => [
        'endpoint' => $endpoint,
        'ssl_verify_disabled' => $sslVerifyDisabled,
        'api_key_configured' => $envApiKey !== '',
        'username_configured' => $envUsername !== '',
        'sender_id_configured' => $envSenderId !== '',
        'using_fallback_credentials' => ($envApiKey === '' || $envUsername === '' || $envSenderId === ''),
    ],
    'warnings' => $warnings,
    'last_log' => $lastLog
]);
