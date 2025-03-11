<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Report.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/roleCheck.php';
require_once __DIR__ . '/../../middleware/validation.php';

use App\Models\Report;
use App\Middleware\Auth;
use App\Middleware\RoleCheck;
use App\Middleware\Validation;

// Verify admin access
if (!Auth::isAuthenticated() || !RoleCheck::isAdmin()) {
    exit;
}

// Get database connection
$db = getDBConnection();
$reportModel = new Report($db);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        try {
            if (isset($_GET['id'])) {
                // Get specific report
                $report = $reportModel->getById($_GET['id']);
                
                if (!$report) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Report not found']);
                    exit;
                }
                
                echo json_encode(['data' => $report]);
            } else {
                // List reports with optional filters
                $filters = [
                    'status' => $_GET['status'] ?? null,
                    'audit_id' => $_GET['audit_id'] ?? null,
                    'user_id' => $_GET['user_id'] ?? null
                ];
                
                $limit = $_GET['limit'] ?? 10;
                $offset = $_GET['offset'] ?? 0;
                
                $reports = $reportModel->getReports($filters, $limit, $offset);
                echo json_encode(['data' => $reports]);
            }
        } catch (Exception $e) {
            error_log("Error in admin reports GET: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
        break;

    case 'PUT':
        try {
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Report ID is required']);
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
            
            // Get current user for review actions
            $currentUser = Auth::getCurrentUser();
            
            // Handle review actions
            if (isset($data['status']) && in_array($data['status'], ['approved', 'rejected'])) {
                $data['reviewer_id'] = $currentUser['id'];
                $data['review_date'] = date('Y-m-d H:i:s');
            }
            
            // Update report
            $success = $reportModel->update($_GET['id'], $data);
            
            if (!$success) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update report']);
                exit;
            }
            
            $report = $reportModel->getById($_GET['id']);
            echo json_encode(['data' => $report]);
        } catch (Exception $e) {
            error_log("Error in admin reports PUT: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
        break;

    case 'POST':
        if (isset($_GET['id']) && isset($_GET['action'])) {
            try {
                $reportId = $_GET['id'];
                $action = $_GET['action'];
                
                switch ($action) {
                    case 'attachment':
                        // Handle file upload
                        if (!isset($_FILES['file'])) {
                            http_response_code(400);
                            echo json_encode(['error' => 'No file uploaded']);
                            exit;
                        }
                        
                        $file = $_FILES['file'];
                        $currentUser = Auth::getCurrentUser();
                        
                        // Basic file validation
                        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                        if (!in_array($file['type'], $allowedTypes)) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Invalid file type']);
                            exit;
                        }
                        
                        // Generate unique filename
                        $filename = uniqid() . '_' . $file['name'];
                        $uploadDir = __DIR__ . '/../../../uploads/reports/';
                        $filepath = $uploadDir . $filename;
                        
                        // Ensure upload directory exists
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        
                        // Move uploaded file
                        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                            http_response_code(500);
                            echo json_encode(['error' => 'Failed to upload file']);
                            exit;
                        }
                        
                        // Save attachment record
                        $attachment = $reportModel->addAttachment([
                            'report_id' => $reportId,
                            'file_name' => $file['name'],
                            'file_path' => '/uploads/reports/' . $filename,
                            'file_type' => $file['type'],
                            'file_size' => $file['size'],
                            'uploaded_by' => $currentUser['id']
                        ]);
                        
                        if (!$attachment) {
                            http_response_code(500);
                            echo json_encode(['error' => 'Failed to save attachment']);
                            exit;
                        }
                        
                        echo json_encode(['data' => $attachment]);
                        break;
                        
                    default:
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid action']);
                        break;
                }
            } catch (Exception $e) {
                error_log("Error in admin reports action: " . $e->getMessage());
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
