<?php
namespace App\Services;

use Exception;
use PDO;

class NotificationService
{
    private $db;
    private $fcmApiKey;
    private $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        
        // You should store your FCM API key in your config
        $this->fcmApiKey = 'YOUR_FCM_SERVER_KEY';
    }

    /**
     * Send notification to a specific user
     */
    public function sendToUser($userId, $title, $message, $data = [])
    {
        try {
            // Get all devices for the user
            $stmt = $this->db->prepare("
                SELECT device_token, device_type 
                FROM user_devices 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($devices)) {
                return false;
            }

            // Group devices by type (iOS, Android, etc.)
            $iosTokens = [];
            $androidTokens = [];

            foreach ($devices as $device) {
                if ($device['device_type'] === 'ios') {
                    $iosTokens[] = $device['device_token'];
                } else {
                    $androidTokens[] = $device['device_token'];
                }
            }

            $results = [];

            // Send to Android devices
            if (!empty($androidTokens)) {
                $results['android'] = $this->sendToAndroid($androidTokens, $title, $message, $data);
            }

            // Send to iOS devices
            if (!empty($iosTokens)) {
                $results['ios'] = $this->sendToIOS($iosTokens, $title, $message, $data);
            }

            return $results;

        } catch (Exception $e) {
            error_log('Error sending notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to all users
     */
    public function sendToAll($title, $message, $data = [])
    {
        try {
            // Get all device tokens
            $stmt = $this->db->query("
                SELECT device_token, device_type 
                FROM user_devices
            ");
            $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($devices)) {
                return false;
            }

            // Group devices by type (iOS, Android, etc.)
            $iosTokens = [];
            $androidTokens = [];

            foreach ($devices as $device) {
                if ($device['device_type'] === 'ios') {
                    $iosTokens[] = $device['device_token'];
                } else {
                    $androidTokens[] = $device['device_token'];
                }
            }

            $results = [];

            // Send to Android devices
            if (!empty($androidTokens)) {
                $results['android'] = $this->sendToAndroid($androidTokens, $title, $message, $data);
            }

            // Send to iOS devices
            if (!empty($iosTokens)) {
                $results['ios'] = $this->sendToIOS($iosTokens, $title, $message, $data);
            }

            return $results;

        } catch (Exception $e) {
            error_log('Error sending notification to all: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to Android devices
     */
    private function sendToAndroid($tokens, $title, $message, $data = [])
    {
        $fields = [
            'registration_ids' => $tokens,
            'notification' => [
                'title' => $title,
                'body' => $message,
                'sound' => 'default',
                'icon' => 'notification_icon',
            ],
            'data' => $data,
            'priority' => 'high',
        ];

        return $this->sendFcmNotification($fields);
    }

    /**
     * Send notification to iOS devices
     */
    private function sendToIOS($tokens, $title, $message, $data = [])
    {
        $fields = [
            'registration_ids' => $tokens,
            'notification' => [
                'title' => $title,
                'body' => $message,
                'sound' => 'default',
                'badge' => '1',
            ],
            'data' => $data,
            'priority' => 'high',
            'content_available' => true,
            'mutable_content' => true,
        ];

        return $this->sendFcmNotification($fields);
    }

    /**
     * Send HTTP request to FCM
     */
    private function sendFcmNotification($fields)
    {
        $headers = [
            'Authorization: key=' . $this->fcmApiKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->fcmUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        $result = curl_exec($ch);
        
        if ($result === false) {
            throw new Exception('Curl failed: ' . curl_error($ch));
        }

        curl_close($ch);

        return json_decode($result, true);
    }
}
