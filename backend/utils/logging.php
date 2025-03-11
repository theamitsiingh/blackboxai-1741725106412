<?php
namespace App\Utils;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

class Logging {
    private static $loggers = [];
    
    /**
     * Get logger instance for specific channel
     * @param string $channel Logger channel name
     * @return Logger Logger instance
     */
    public static function getLogger($channel = 'app') {
        if (!isset(self::$loggers[$channel])) {
            self::$loggers[$channel] = self::createLogger($channel);
        }
        
        return self::$loggers[$channel];
    }
    
    /**
     * Create new logger instance
     * @param string $channel Logger channel name
     * @return Logger Logger instance
     */
    private static function createLogger($channel) {
        $logger = new Logger($channel);
        
        // Create log directory if it doesn't exist
        $logDir = __DIR__ . '/../logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        // Define log format
        $dateFormat = "Y-m-d H:i:s";
        $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $formatter = new LineFormatter($output, $dateFormat);
        
        // Add handlers based on environment
        if ($_ENV['DEBUG'] ?? false) {
            // In debug mode, log everything to stream
            $streamHandler = new StreamHandler('php://stdout', Logger::DEBUG);
            $streamHandler->setFormatter($formatter);
            $logger->pushHandler($streamHandler);
        }
        
        // Always log to rotating file
        $fileHandler = new RotatingFileHandler(
            $logDir . '/' . $channel . '.log',
            30, // Keep 30 days of logs
            Logger::INFO
        );
        $fileHandler->setFormatter($formatter);
        $logger->pushHandler($fileHandler);
        
        return $logger;
    }
    
    /**
     * Log API request
     * @param array $request Request details
     */
    public static function logApiRequest($request) {
        $logger = self::getLogger('api');
        
        $context = [
            'method' => $_SERVER['REQUEST_METHOD'],
            'path' => $_SERVER['REQUEST_URI'],
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'request_id' => uniqid(),
            'payload' => $request
        ];
        
        $logger->info('API Request', $context);
    }
    
    /**
     * Log API response
     * @param int $statusCode Response status code
     * @param array $response Response data
     * @param string $requestId Related request ID
     */
    public static function logApiResponse($statusCode, $response, $requestId) {
        $logger = self::getLogger('api');
        
        $context = [
            'status_code' => $statusCode,
            'request_id' => $requestId,
            'response' => $response
        ];
        
        $logger->info('API Response', $context);
    }
    
    /**
     * Log authentication event
     * @param string $event Event type
     * @param array $context Event context
     */
    public static function logAuthEvent($event, $context) {
        $logger = self::getLogger('auth');
        $logger->info($event, $context);
    }
    
    /**
     * Log audit activity
     * @param string $action Action performed
     * @param array $context Action context
     */
    public static function logAuditActivity($action, $context) {
        $logger = self::getLogger('audit');
        $logger->info($action, $context);
    }
    
    /**
     * Log compliance activity
     * @param string $action Action performed
     * @param array $context Action context
     */
    public static function logComplianceActivity($action, $context) {
        $logger = self::getLogger('compliance');
        $logger->info($action, $context);
    }
    
    /**
     * Log report activity
     * @param string $action Action performed
     * @param array $context Action context
     */
    public static function logReportActivity($action, $context) {
        $logger = self::getLogger('report');
        $logger->info($action, $context);
    }
    
    /**
     * Log error
     * @param string $message Error message
     * @param array $context Error context
     * @param string $channel Logger channel
     */
    public static function logError($message, $context = [], $channel = 'app') {
        $logger = self::getLogger($channel);
        $logger->error($message, $context);
    }
    
    /**
     * Log security event
     * @param string $event Event description
     * @param array $context Event context
     */
    public static function logSecurityEvent($event, $context) {
        $logger = self::getLogger('security');
        $logger->warning($event, $context);
    }
    
    /**
     * Log system event
     * @param string $event Event description
     * @param array $context Event context
     */
    public static function logSystemEvent($event, $context) {
        $logger = self::getLogger('system');
        $logger->info($event, $context);
    }
    
    /**
     * Log file operation
     * @param string $operation Operation performed
     * @param array $context Operation context
     */
    public static function logFileOperation($operation, $context) {
        $logger = self::getLogger('file');
        $logger->info($operation, $context);
    }
}
