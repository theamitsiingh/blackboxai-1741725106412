<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/logging.php';
require_once __DIR__ . '/utils/helpers.php';

use App\Utils\Logging;
use App\Utils\Helpers;

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get the request path
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request = trim($request, '/');
$segments = explode('/', $request);

// Remove 'api' from segments if present
if ($segments[0] === 'api') {
    array_shift($segments);
}

// Log API request
Logging::logApiRequest([
    'path' => $request,
    'method' => $_SERVER['REQUEST_METHOD'],
    'params' => $_GET,
    'body' => file_get_contents('php://input')
]);

try {
    // Route the request
    switch ($segments[0]) {
        case 'auth':
            if (isset($segments[1])) {
                $file = __DIR__ . '/api/auth/' . $segments[1] . '.php';
                if (file_exists($file)) {
                    require_once $file;
                } else {
                    throw new Exception('Invalid auth endpoint');
                }
            }
            break;
            
        case 'admin':
            if (isset($segments[1])) {
                $file = __DIR__ . '/api/admin/' . $segments[1] . '.php';
                if (file_exists($file)) {
                    require_once $file;
                } else {
                    throw new Exception('Invalid admin endpoint');
                }
            }
            break;
            
        case 'user':
            if (isset($segments[1])) {
                $file = __DIR__ . '/api/user/' . $segments[1] . '.php';
                if (file_exists($file)) {
                    require_once $file;
                } else {
                    throw new Exception('Invalid user endpoint');
                }
            }
            break;
            
        case 'health':
            // Health check endpoint
            echo json_encode([
                'status' => 'healthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0.0'
            ]);
            break;
            
        default:
            throw new Exception('Invalid endpoint');
    }
} catch (Exception $e) {
    $statusCode = 500;
    
    // Set appropriate status code based on exception
    if ($e->getMessage() === 'Invalid endpoint' || 
        $e->getMessage() === 'Invalid auth endpoint' || 
        $e->getMessage() === 'Invalid admin endpoint' || 
        $e->getMessage() === 'Invalid user endpoint') {
        $statusCode = 404;
    }
    
    http_response_code($statusCode);
    
    // Log error
    Logging::logError($e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Return error response
    echo json_encode(Helpers::formatErrorResponse(
        $statusCode === 404 ? 'Endpoint not found' : 'Internal server error',
        [],
        $statusCode
    ));
}

// Log response
Logging::logApiResponse(
    http_response_code(),
    json_decode(ob_get_contents(), true),
    uniqid()
);
