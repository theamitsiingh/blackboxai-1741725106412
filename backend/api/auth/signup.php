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
    $validation = Validation::validate($data, Validation::getUserRegistrationRules());
    
    if (!$validation['isValid']) {
        http_response_code(400);
        echo json_encode(['error' => 'Validation failed', 'details' => $validation['errors']]);
        exit;
    }
    
    // Get database connection
    $db = getDBConnection();
    $userModel = new User($db);
    
    // Check if email already exists
    if ($userModel->getByEmail($validation['sanitized']['email'])) {
        http_response_code(409);
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }
    
    // Set default role if not provided
    $data['role'] = $data['role'] ?? 'user';
    
    // Additional password validation
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $data['password'])) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Password must contain at least 8 characters, including uppercase, lowercase, and numbers'
        ]);
        exit;
    }
    
    // Create new user
    $newUser = $userModel->create([
        'username' => $validation['sanitized']['username'],
        'email' => $validation['sanitized']['email'],
        'password' => $data['password'],
        'role' => $data['role']
    ]);
    
    if (!$newUser) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create user']);
        exit;
    }
    
    // Generate JWT token
    $token = Auth::generateToken($newUser);
    
    // Return success response
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'User registered successfully',
        'token' => $token,
        'user' => [
            'id' => $newUser['id'],
            'username' => $newUser['username'],
            'email' => $newUser['email'],
            'role' => $newUser['role']
        ]
    ]);

} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
