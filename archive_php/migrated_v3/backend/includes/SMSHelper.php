<?php
class SMSHelper {
    public function send($to, $message) {
        $api_key = getenv('WIGAL_API_KEY') ?: (getenv('SMS_API_KEY') ?: '$2y$10$6oYYcjc6Ge3/W.P.1Yqk6eHBs0ERVFR6IaBQ2qpYGBnMYp28B3uPe');
        $username = getenv('WIGAL_USERNAME') ?: (getenv('SMS_USERNAME') ?: 'amanvid');
        $sender_id = getenv('WIGAL_SENDER_ID') ?: (getenv('SMS_SENDER_ID') ?: 'INFOTESS');
        $endpoint = getenv('WIGAL_SMS_ENDPOINT') ?: (getenv('SMS_API_URL') ?: 'https://frogapi.wigal.com.gh/api/v3/sms/send');

        $isVercel = (getenv('VERCEL') === '1') || (getenv('NOW_REGION') !== false);
        $dir = __DIR__ . '/../sms_logs';
        if (!$isVercel && !is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        if ($api_key === '') {
            if (!$isVercel) {
                $logEntry = "[" . date('Y-m-d H:i:s') . "] To: $to | Message: $message | Error: Missing SMS API key (WIGAL_API_KEY/SMS_API_KEY)." . PHP_EOL;
                @file_put_contents($dir . '/sms.log', $logEntry, FILE_APPEND);
            }
            return false;
        }

        $postData = array(
            'senderid' => $sender_id,
            'destinations' => array(
                array(
                    'destination' => $to,
                    'message' => $message,
                    'msgid' => uniqid('MSG'),
                    'smstype' => 'text'
                )
            )
        );

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'API-KEY: ' . $api_key,
            'USERNAME: ' . $username
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        
        // Disable SSL verification for local WAMP if needed, but best left on in prod
        $disableSslVerify = getenv('SMS_DISABLE_SSL_VERIFY');
        $host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : '';
        $isLocalHost = stripos($host, 'localhost') !== false || stripos($host, '127.0.0.1') !== false;
        $disableSslOnLocal = !$isVercel && ($disableSslVerify === false || $disableSslVerify === '') && $isLocalHost;
        $sslVerifyDisabled = ($disableSslVerify === '1' || strtolower((string)$disableSslVerify) === 'true' || $disableSslOnLocal);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, !$sslVerifyDisabled);
        if ($sslVerifyDisabled) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$isVercel) {
            $logEntry = "[" . date('Y-m-d H:i:s') . "] To: $to | Message: $message | HTTP: $httpCode | API Response: $response | Curl Error: $error" . PHP_EOL;
            @file_put_contents($dir . '/sms.log', $logEntry, FILE_APPEND);
        }

        if ($response === false || $error !== '') {
            return false;
        }

        $decoded = json_decode((string)$response, true);
        if (is_array($decoded)) {
            if (isset($decoded['status'])) {
                $statusValue = strtoupper(trim((string)$decoded['status']));
                if (in_array($statusValue, ['ACCEPTD', 'ACCEPTED', 'SUCCESS', 'OK', 'TRUE'], true)) {
                    return true;
                }
            }
            if (isset($decoded['message'])) {
                $messageValue = strtoupper((string)$decoded['message']);
                if (strpos($messageValue, 'ACCEPTED') !== false || strpos($messageValue, 'ACCEPT') !== false) {
                    return true;
                }
            }
            if (isset($decoded['status']) && $decoded['status'] === true) {
                return true;
            }
            if (isset($decoded['success']) && $decoded['success'] === true) {
                return true;
            }
            if (isset($decoded['code']) && in_array((int)$decoded['code'], [200, 201, 202], true)) {
                return true;
            }
            return false;
        }

        return $httpCode >= 200 && $httpCode < 300;
    }
}
?>
