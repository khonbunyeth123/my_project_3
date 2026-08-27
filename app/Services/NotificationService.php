<?php
declare(strict_types=1);
namespace App\Services;
class NotificationService
{
    private string $serviceAccountPath;
    private string $projectId;
    public function __construct()
    {
        $this->serviceAccountPath = __DIR__ . '/../../config/firebase-service-account.json';
        $this->projectId = 'hrm-doorstep';
    }
        public function sendLeaveApproved(int $employeeId): void
    {
        $fcmToken = $this->getFcmToken($employeeId);
        error_log("[DEBUG] sendLeaveApproved employeeId={$employeeId} token=" . ($fcmToken ?? 'NULL'));
        if (!$fcmToken) return;
        $this->send($fcmToken, ['title' => '✅ Leave Approved', 'body' => 'Your leave request has been approved.'], ['type' => 'leave_status', 'status' => 'approved']);
    }
    public function sendLeaveRejected(int $employeeId, string $remark = ''): void
    {
        $fcmToken = $this->getFcmToken($employeeId);
        if (!$fcmToken) return;
        $body = 'Your leave request has been rejected.';
        if ($remark !== '') $body .= " Reason: $remark";
        $this->send($fcmToken, ['title' => '❌ Leave Rejected', 'body' => $body], ['type' => 'leave_status', 'status' => 'rejected', 'remark' => $remark]);
    }

    public function sendCalendarEventNotification(int $employeeId, string $title, string $body, array $data = []): void
    {
        $this->sendCalendarEventNotifications([$employeeId], $title, $body, $data);
    }

    /**
     * Send one calendar notification to many employees, de-duplicating shared FCM tokens.
     *
     * @param int[] $employeeIds
     */
    public function sendCalendarEventNotifications(array $employeeIds, string $title, string $body, array $data = []): void
    {
        $payload = array_merge(['type' => 'calendar_event'], $data);
        foreach ($this->collectUniqueFcmTokens($employeeIds) as $fcmToken) {
            $this->send($fcmToken, ['title' => $title, 'body' => $body], $payload);
        }
    }

    public function saveFcmToken(int $employeeId, string $fcmToken): bool
    {
        $db = \App\Core\Database::getInstance()->getConnection();

        // Clear this token from any other employee that currently holds it
        $clear = $db->prepare('UPDATE tbl_employees SET fcm_token = NULL WHERE fcm_token = :fcm_token AND id != :id');
        $clear->execute([':fcm_token' => $fcmToken, ':id' => $employeeId]);

        $stmt = $db->prepare('UPDATE tbl_employees SET fcm_token = :fcm_token WHERE id = :id');
        return $stmt->execute([':fcm_token' => $fcmToken, ':id' => $employeeId]);
    }
    protected function getFcmToken(int $employeeId): ?string
    {
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT fcm_token FROM tbl_employees WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $employeeId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $token = $row['fcm_token'] ?? null;
        return ($token !== null && $token !== '') ? $token : null;
    }

    /**
     * @param int[] $employeeIds
     * @return string[]
     */
    private function collectUniqueFcmTokens(array $employeeIds): array
    {
        $tokens = [];
        foreach (array_values(array_unique(array_filter(array_map('intval', $employeeIds)))) as $employeeId) {
            $token = $this->getFcmToken($employeeId);
            if ($token === null) {
                continue;
            }

            $tokens[$token] = true;
        }

        return array_keys($tokens);
    }

    protected function send(string $fcmToken, array $notification, array $data = []): void
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) { error_log('[NotificationService] Failed to obtain FCM access token.'); return; }
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
        $payload = json_encode(['message' => ['token' => $fcmToken, 'notification' => $notification, 'data' => array_map('strval', $data), 'android' => ['notification' => ['sound' => 'default']], 'apns' => ['payload' => ['aps' => ['sound' => 'default']]]]]);
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken, 'Content-Type: application/json']]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode !== 200) error_log("[NotificationService] FCM send failed ($httpCode): $response");
    }
    private function getAccessToken(): ?string
    {
        if (!file_exists($this->serviceAccountPath)) { error_log('[NotificationService] Service account file not found.'); return null; }
        $serviceAccount = json_decode(file_get_contents($this->serviceAccountPath), true);
        if (!$serviceAccount) return null;
        $now = time();
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claim = $this->base64UrlEncode(json_encode(['iss' => $serviceAccount['client_email'], 'scope' => 'https://www.googleapis.com/auth/firebase.messaging', 'aud' => 'https://oauth2.googleapis.com/token', 'iat' => $now, 'exp' => $now + 3600]));
        $signingInput = "$header.$claim";
        $privateKey = openssl_pkey_get_private($serviceAccount['private_key']);
        openssl_sign($signingInput, $signature, $privateKey, 'SHA256');
        $jwt = "$signingInput." . $this->base64UrlEncode($signature);
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt])]);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);
        return $response['access_token'] ?? null;
    }
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
