<?php
class SMSHelper {
    public function send($to, $message) {
        $api_key = getenv('WIGAL_API_KEY') ?: '';
        $username = getenv('WIGAL_USERNAME') ?: 'amanvid'; // using sender ID as username per standard Wigal API docs
        $sender_id = getenv('WIGAL_SENDER_ID') ?: 'INFOTESS'; // Must be an approved sender ID, falling back to INFOTESS
        $endpoint = getenv('WIGAL_SMS_ENDPOINT') ?: 'https://frogapi.wigal.com.gh/api/v3/sms/send';

        if ($api_key === '') {
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, !($disableSslVerify === '1' || strtolower((string)$disableSslVerify) === 'true'));

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        $isVercel = (getenv('VERCEL') === '1') || (getenv('NOW_REGION') !== false);
        if (!$isVercel) {
            $dir = __DIR__ . '/../sms_logs';
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }

            $logEntry = "[" . date('Y-m-d H:i:s') . "] To: $to | Message: $message | API Response: $response | Curl Error: $error" . PHP_EOL;
            @file_put_contents($dir . '/sms.log', $logEntry, FILE_APPEND);
        }
        
        return $response ? true : false;
    }
}
?>