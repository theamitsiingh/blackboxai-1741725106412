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

// Verify user authentication
if (!Auth::isAuthenticated() || !RoleCheck::isUser()) {
    exit;
}

// Get current user
$currentUser = Auth::getCurrentUser();

// Get database connection
$db = getDBConnection();
$auditModel = new Audit($db);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        try {
            if (isset($_GET['id'])) {
                // Get specific audit
                $audit = $auditModel->getById($_GET['id']);
                
                if (!$audit) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Audit not found']);
                    exit;
                }
                
                // Check if user has access to this audit
                if ($audit['user_id'] !== $currentUser['id'] && $currentUser['role'] !== 'admin') {
                    http_response_code(403);
                    echo json_encode(['error' => 'Access denied']);
                    exit;
                }
                
                echo json_encode(['data' => $audit]);
            } else {
                // List audits for current user
                $filters = [
                    'user_id' => $currentUser['id'],
                    'status' => $_GET['status'] ?? null,
                    'type' => $_GET['type'] ?? null
                ];
                
                $limit = $_GET['limit'] ?? 10;
                $offset = $_GET['offset'] ?? 0;
                
                $audits = $auditModel->getAudits($filters, $limit, $offset);
                echo json_encode(['data' => $audits]);
            }
        } catch (Exception $e) {
            error_log("Error in user audits GET: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
        break;

    case 'POST':
        if (isset($_GET['id']) && isset($_GET['action'])) {
            try {
                $auditId = $_GET['id'];
                $action = $_GET['action'];
                
                // Verify user has access to this audit
                $audit = $auditModel->getById($auditId);
                if (!$audit || ($audit['user_id'] !== $currentUser['id'] && $currentUser['role'] !== 'admin')) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Access denied']);
                    exit;
                }
                
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
                        
                    case 'update-status':
                        // Allow users to update only specific fields
                        $json = file_get_contents('php://input');
                        $data = Validation::validateJSON($json);
                        
                        if (!$data || empty($data['status'])) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Invalid status data']);
                            exit;
                        }
                        
                        // Only allow specific status transitions for users
                        $allowedStatuses = ['in_progress', 'completed'];
                        if (!in_array($data['status'], $allowedStatuses)) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Invalid status transition']);
                            exit;
                        }
                        
                        $success = $auditModel->update($auditId, ['status' => $data['status']]);
                        
                        if (!$success) {
                            http_response_code(500);
                            echo json_encode(['error' => 'Failed to update status']);
                            exit;
                        }
                        
                        $updatedAudit = $auditModel->getById($auditId);
                        echo json_encode(['data' => $updatedAudit]);
                        break;
                        
                    default:
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid action']);
                        break;
                }
            } catch (Exception $e) {
                error_log("Error in user audits action: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Internal server error']);
            }
        } else {
            // Create new audit (if user has permission)
            try {
                // Check if user has permission to create audits
                if (!RoleCheck::hasPermission('create_audits')) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Permission denied']);
                    exit;
                }
                
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
                
                // Set current user as creator
                $data['user_id'] = $currentUser['id'];
                
                // Create new audit
                $audit = $auditModel->create($data);
                
                if (!$audit) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to create audit']);
                    exit;
                }
                
                http_response_code(201);
                echo json_encode(['data' => $audit]);
            } catch (Exception $e) {
                error_log("Error in user audits POST: " . $e->getMessage());
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
