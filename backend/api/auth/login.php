<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../middleware/validation.php';
require_once __DIR__ . '/../../middleware/auth.php';

use App\Models\User;
use App\Middleware\Validation;
use App\Middleware\Auth;

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = Validation::validateJSON($json);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        exit;
    }
    
    // Validate input
    $validation = Validation::validate($data, Validation::getUserLoginRules());
    
    if (!$validation['isValid']) {
        http_response_code(400);
        echo json_encode(['error' => 'Validation failed', 'details' => $validation['errors']]);
        exit;
    }
    
    // Get database connection
    $db = getDBConnection();
    $userModel = new User($db);
    
    // Check if user exists
    $user = $userModel->getByEmail($validation['sanitized']['email']);
    
    if (!$user || !$userModel->verifyPassword($data['password'], $user['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
        exit;
    }
    
    // Generate JWT token
    $token = Auth::generateToken($user);
    
    // Return success response with token
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
