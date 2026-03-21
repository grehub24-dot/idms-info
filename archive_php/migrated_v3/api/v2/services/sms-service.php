<?php

declare(strict_types=1);

/**
 * SMS Service for INFOTESS
 * Production-ready SMS integration
 */

class SMSService {
    private string $apiKey;
    private string $apiUrl;
    private bool $enabled;
    
    public function __construct() {
        $this->apiKey = env('SMS_API_KEY', '');
        $this->apiUrl = env('SMS_API_URL', 'https://api.sms-provider.com/send');
        $this->enabled = env('SMS_ENABLED', 'false') === 'true';
    }
    
    /**
     * Send temporary password via SMS
     */
    public function sendTemporaryPassword(string $phoneNumber, string $tempPassword, string $studentName = ''): array {
        if (!$this->enabled) {
            return [
                'success' => false,
                'message' => 'SMS service is disabled',
                'debug_info' => 'Enable SMS by setting SMS_ENABLED=true and SMS_API_KEY'
            ];
        }
        
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => 'SMS API key not configured',
                'debug_info' => 'Set SMS_API_KEY in your environment variables'
            ];
        }
        
        // Validate phone number format
        if (!$this->validatePhoneNumber($phoneNumber)) {
            return [
                'success' => false,
                'message' => 'Invalid phone number format',
                'debug_info' => 'Phone number must be in international format: +1234567890'
            ];
        }
        
        $message = $this->generateMessage($tempPassword, $studentName);
        
        try {
            $response = $this->sendSMS($phoneNumber, $message);
            
            if ($response['success']) {
                // Log successful SMS sent
                error_log("SMS sent successfully to {$phoneNumber} for temporary password");
                
                return [
                    'success' => true,
                    'message' => 'Temporary password sent via SMS successfully',
                    'phone_number' => $this->maskPhoneNumber($phoneNumber),
                    'sent_at' => date('Y-m-d H:i:s')
                ];
            } else {
                error_log("SMS failed to send to {$phoneNumber}: " . $response['error']);
                
                return [
                    'success' => false,
                    'message' => 'Failed to send SMS',
                    'error' => $response['error']
                ];
            }
            
        } catch (Throwable $e) {
            error_log("SMS service error: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'SMS service temporarily unavailable',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate phone number format
     */
    private function validatePhoneNumber(string $phoneNumber): bool {
        // Basic international phone number validation
        return preg_match('/^\+[1-9]\d{1,14}$/', $phoneNumber) === 1;
    }
    
    /**
     * Generate SMS message
     */
    private function generateMessage(string $tempPassword, string $studentName): string {
        $appName = 'INFOTESS';
        
        if ($studentName) {
            return "Hello {$studentName}, your temporary password for {$appName} is: {$tempPassword}. Please use this to login and set your permanent password. This password expires in 24 hours.";
        } else {
            return "Your temporary password for {$appName} is: {$tempPassword}. Please use this to login and set your permanent password. This password expires in 24 hours.";
        }
    }
    
    /**
     * Send SMS via API
     */
    private function sendSMS(string $phoneNumber, string $message): array {
        // This is a mock implementation - replace with your actual SMS provider API
        $payload = [
            'api_key' => $this->apiKey,
            'to' => $phoneNumber,
            'message' => $message,
            'sender' => 'INFOTESS'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'CURL error: ' . $error
            ];
        }
        
        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => 'HTTP error: ' . $httpCode
            ];
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['success']) && $result['success']) {
            return [
                'success' => true,
                'message_id' => $result['message_id'] ?? null
            ];
        } else {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Unknown SMS API error'
            ];
        }
    }
    
    /**
     * Mask phone number for privacy
     */
    private function maskPhoneNumber(string $phoneNumber): string {
        if (strlen($phoneNumber) <= 4) {
            return str_repeat('*', strlen($phoneNumber));
        }
        
        $visible = substr($phoneNumber, -4);
        $masked = str_repeat('*', strlen($phoneNumber) - 4);
        
        return $masked . $visible;
    }
}
