<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/SecurityConfig.php';

class AuthController {

    public function login() {

        $input = json_decode(
            file_get_contents("php://input"),
            true
        );

        $api_key = $input['api_key'] ?? '';

        // Validate API key
        if (empty($api_key)) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error'   => 'API key required'
            ]);
            return;
        }

        if ($api_key !== SecurityConfig::API_KEY) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error'   => 'Invalid API key'
            ]);
            return;
        }

        $cn = Database::connect();

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        // Check if IP is whitelisted
        if (!in_array($ip, SecurityConfig::ALLOWED_IPS)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error'   => 'IP not allowed'
            ]);
            return;
        }

        // Deactivate any existing tokens for this IP
        $stmt = mysqli_prepare($cn,
            "UPDATE rpa_api_tokens 
             SET is_active = 0 
             WHERE ip_address = ?"
        );
        mysqli_stmt_bind_param($stmt, 's', $ip);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Generate new token
        $token      = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        // Store token
        $stmt = mysqli_prepare($cn,
            "INSERT INTO rpa_api_tokens 
             (token, ip_address, expires_at, is_active) 
             VALUES (?, ?, ?, 1)"
        );
        mysqli_stmt_bind_param($stmt, 'sss', $token, $ip, $expires_at);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo json_encode([
            'success'    => true,
            'token'      => $token,
            'expires_at' => $expires_at,
            'message'    => 'Token valid for 5 minutes of inactivity'
        ]);
    }
}