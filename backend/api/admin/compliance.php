<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Compliance.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/roleCheck.php';
require_once __DIR__ . '/../../middleware/validation.php';

use App\Models\Compliance;
use App\Middleware\Auth;
use App\Middleware\RoleCheck;
use App\Middleware\Validation;

// Verify admin access
if (!Auth::isAuthenticated() || !RoleCheck::isAdmin()) {
    exit;
}

// Get database connection
$db = getDBConnection();
$complianceModel = new Compliance($db);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        try {
            // Handle different GET operations
            if (isset($_GET['standard_id'])) {
                // Get specific compliance standard with its requirements
                $standard = $complianceModel->getStandardById($_GET['standard_id']);
                
                if (!$standard) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Compliance standard not found']);
                    exit;
                }
                
                echo json_encode(['data' => $standard]);
            } elseif (isset($_GET['audit_id'])) {
                // Get compliance assessments for specific audit
                $assessments = $complianceModel->getAssessmentsByAuditId($_GET['audit_id']);
                echo json_encode(['data' => $assessments]);
            } elseif (isset($_GET['summary']) && isset($_GET['audit_id'])) {
                // Get compliance summary for specific audit
                $summary = $complianceModel->getComplianceSummary($_GET['audit_id']);
                echo json_encode(['data' => $summary]);
            } else {
                // List all compliance standards
                $standards = $complianceModel->getAllStandards();
                echo json_encode(['data' => $standards]);
            }
        } catch (Exception $e) {
            error_log("Error in admin compliance GET: " . $e->getMessage());
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
            
            // Handle different POST operations based on the endpoint
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'create_assessment':
                        // Validate assessment data
                        $validation = Validation::validate($data, [
                            'requirement_id' => ['required', 'number'],
                            'audit_id' => ['required', 'number'],
                            'status' => ['required', 'string'],
                            'evidence' => ['string'],
                            'notes' => ['string']
                        ]);
                        
                        if (!$validation['isValid']) {
                            http_response_code(400);
                            echo json_encode([
                                'error' => 'Validation failed',
                                'details' => $validation['errors']
                            ]);
                            exit;
                        }
                        
                        // Add current user as assessor
                        $currentUser = Auth::getCurrentUser();
                        $data['assessed_by'] = $currentUser['id'];
                        
                        // Create assessment
                        $assessment = $complianceModel->createAssessment($data);
                        
                        if (!$assessment) {
                            http_response_code(500);
                            echo json_encode(['error' => 'Failed to create assessment']);
                            exit;
                        }
                        
                        http_response_code(201);
                        echo json_encode(['data' => $assessment]);
                        break;
                        
                    default:
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid action']);
                        break;
                }
            }
        } catch (Exception $e) {
            error_log("Error in admin compliance POST: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
        break;

    case 'PUT':
        try {
            if (!isset($_GET['assessment_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Assessment ID is required']);
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
            
            // Validate update data
            $validation = Validation::validate($data, [
                'status' => ['string'],
                'evidence' => ['string'],
                'notes' => ['string']
            ]);
            
            if (!$validation['isValid']) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Validation failed',
                    'details' => $validation['errors']
                ]);
                exit;
            }
            
            // Update assessment
            $success = $complianceModel->updateAssessment($_GET['assessment_id'], $data);
            
            if (!$success) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update assessment']);
                exit;
            }
            
            $assessment = $complianceModel->getAssessmentById($_GET['assessment_id']);
            echo json_encode(['data' => $assessment]);
        } catch (Exception $e) {
            error_log("Error in admin compliance PUT: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
