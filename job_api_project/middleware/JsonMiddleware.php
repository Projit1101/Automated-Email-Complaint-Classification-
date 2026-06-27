<?php

require_once __DIR__ . '/../config/SecurityConfig.php';
require_once __DIR__ . '/../config/Database.php';

class JsonMiddleware {

    public static function handle() {

        header("Content-Type: application/json");

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/api/auth/login') !== false) {
            return;
        }

        self::checkIpWhitelist();
        self::checkToken();
    }

    private static function checkIpWhitelist() {

        $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';

        if (!in_array($client_ip, SecurityConfig::ALLOWED_IPS)) {
            self::reject(403, 'IP not allowed: ' . $client_ip);
        }
    }

    private static function checkToken() {

        $token = '';

        if (isset($_SERVER['HTTP_X_AUTH_TOKEN'])) {
            $token = $_SERVER['HTTP_X_AUTH_TOKEN'];
        } elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
            $token   = $headers['X-Auth-Token'] ?? $headers['x-auth-token'] ?? '';
        }

        if (empty($token)) {
            self::reject(401, 'Token missing — please login first');
        }

        $cn  = Database::connect();
        $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
        $now = date('Y-m-d H:i:s');

        $stmt = mysqli_prepare($cn,
            "SELECT id FROM rpa_api_tokens 
             WHERE token = ? 
             AND ip_address = ? 
             AND is_active = 1 
             AND expires_at > ?"
        );
        mysqli_stmt_bind_param($stmt, 'sss', $token, $ip, $now);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row    = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$row) {
            self::reject(401, 'Token expired or invalid — please login again');
        }

        $new_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        $stmt = mysqli_prepare($cn,
            "UPDATE rpa_api_tokens 
             SET expires_at = ? 
             WHERE token = ?"
        );
        mysqli_stmt_bind_param($stmt, 'ss', $new_expiry, $token);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    private static function reject($code, $message) {

        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error'   => $message
        ]);
        exit;
    }
}