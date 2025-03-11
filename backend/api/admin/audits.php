<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Audit.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/roleCheck.php';
require_once __DIR__ . '/../../middleware/validation.php';

use App\Models\Audit;
use App\Middleware\Auth;
use App\Middleware\RoleCheck;
use App\Middleware\Validation;

// Verify admin access
if (!Auth::isAuthenticated() || !RoleCheck::isAdmin()) {
    exit;
}

// Get database connection
$db = getDBConnection();
$auditModel = new Audit($db);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        try {
            // Handle different GET operations based on query parameters
            if (isset($_GET['id'])) {
                // Get specific audit
                $audit = $auditModel->getById($_GET['id']);
                if (!$audit) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Audit not found']);
                    exit;
                }
                echo json_encode(['data' => $audit]);
            } else {
                // List audits with optional filters
                $filters = [
                    'status' => $_GET['status'] ?? null,
                    'type' => $_GET['type'] ?? null,
                    'user_id' => $_GET['user_id'] ?? null
                ];
                
                $limit = $_GET['limit'] ?? 10;
                $offset = $_GET['offset'] ?? 0;
                
                $audits = $auditModel->getAudits($filters, $limit, $offset);
                echo json_encode(['data' => $audits]);
            }
        } catch (Exception $e) {
            error_log("Error in admin audits GET: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
        break;

    case 'POST':
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
            $validation = Validation::validate($data, Validation::getAuditRules());
            
            if (!$validation['isValid']) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Validation failed',
                    'details' => $validation['errors']
                ]);
                exit;
            }
            
            // Create new audit
            $currentUser = Auth::getCurrentUser();
            $data['user_id'] = $currentUser['id'];
            
            $audit = $auditModel->create($data);
            
            if (!$audit) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create audit']);
                exit;
            }
            
            http_response_code(201);
            echo json_encode(['data' => $audit]);
        } catch (Exception $e) {
            error_log("Error in admin audits POST: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
        break;

    case 'PUT':
        try {
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Audit ID is required']);
                exit;
            }
            
            // Get JSON input
            $json = file_get_contents('php://input');
            $data = Validation::validateJSON($json);
            
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON data']);
                exit;
            }
            
            // Validate input
            $validation = Validation::validate($data, [
                'status' => ['string'],
                'findings' => ['string'],
                'recommendations' => ['string']
            ]);
            
            if (!$validation['isValid']) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Validation failed',
                    'details' => $validation['errors']
                ]);
                exit;
            }
            
            // Update audit
            $success = $auditModel->update($_GET['id'], $data);
            
            if (!$success) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update audit']);
                exit;
            }
            
            $audit = $auditModel->getById($_GET['id']);
            echo json_encode(['data' => $audit]);
        } catch (Exception $e) {
            error_log("Error in admin audits PUT: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
        break;

    case 'POST':
        if (isset($_GET['id']) && isset($_GET['action'])) {
            try {
                $auditId = $_GET['id'];
                $action = $_GET['action'];
                
                switch ($action) {
                    case 'comment':
                        // Add comment to audit
                        $json = file_get_contents('php://input');
                        $data = Validation::validateJSON($json);
                        
                        if (!$data || empty($data['comment'])) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Invalid comment data']);
                            exit;
                        }
                        
                        $currentUser = Auth::getCurrentUser();
                        $commentData = [
                            'audit_id' => $auditId,
                            'user_id' => $currentUser['id'],
                            'comment' => $data['comment']
                        ];
                        
                        $comment = $auditModel->addComment($commentData);
                        
                        if (!$comment) {
                            http_response_code(500);
                            echo json_encode(['error' => 'Failed to add comment']);
                            exit;
                        }
                        
                        echo json_encode(['data' => $comment]);
                        break;
                        
                    default:
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid action']);
                        break;
                }
            } catch (Exception $e) {
                error_log("Error in admin audits action: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Internal server error']);
            }
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
