<?php
namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class Auth {
    /**
     * Verify JWT token from Authorization header
     * @return array|false Decoded token data or false on failure
     */
    public static function verifyToken() {
        try {
            $headers = getallheaders();
            if (!isset($headers['Authorization'])) {
                return false;
            }

            $authHeader = $headers['Authorization'];
            $token = str_replace('Bearer ', '', $authHeader);

            $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
            return (array) $decoded;
        } catch (Exception $e) {
            error_log("Token verification failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate JWT token for user
     * @param array $userData User data to encode in token
     * @return string JWT token
     */
    public static function generateToken($userData) {
        $issuedAt = time();
        $expire = $issuedAt + JWT_EXPIRATION;

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'user' => [
                'id' => $userData['id'],
                'email' => $userData['email'],
                'role' => $userData['role']
            ]
        ];

        return JWT::encode($payload, JWT_SECRET, 'HS256');
    }

    /**
     * Middleware to check if user is authenticated
     * @return bool Authentication status
     */
    public static function isAuthenticated() {
        $tokenData = self::verifyToken();
        if (!$tokenData) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized access']);
            return false;
        }
        return true;
    }

    /**
     * Get current authenticated user data from token
     * @return array|null User data or null if not authenticated
     */
    public static function getCurrentUser() {
        $tokenData = self::verifyToken();
        return $tokenData ? $tokenData['user'] : null;
    }
}
