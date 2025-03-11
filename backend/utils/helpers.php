<?php
namespace App\Utils;

class Helpers {
    /**
     * Generate a secure random token
     * @param int $length Token length
     * @return string Generated token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Format date to standard format
     * @param string $date Date string
     * @param string $format Desired format (default: Y-m-d H:i:s)
     * @return string Formatted date
     */
    public static function formatDate($date, $format = 'Y-m-d H:i:s') {
        return date($format, strtotime($date));
    }
    
    /**
     * Sanitize file name
     * @param string $filename Original filename
     * @return string Sanitized filename
     */
    public static function sanitizeFileName($filename) {
        // Remove any path components
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        // Ensure the filename is not too long
        $maxLength = 255;
        if (strlen($filename) > $maxLength) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $filename = substr(pathinfo($filename, PATHINFO_FILENAME), 0, $maxLength - strlen($ext) - 1) . '.' . $ext;
        }
        
        return $filename;
    }
    
    /**
     * Generate a unique file name
     * @param string $originalName Original file name
     * @return string Unique file name
     */
    public static function generateUniqueFileName($originalName) {
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        return uniqid() . '_' . time() . '.' . $ext;
    }
    
    /**
     * Get file mime type
     * @param string $filepath Path to file
     * @return string|false Mime type or false on failure
     */
    public static function getFileMimeType($filepath) {
        return mime_content_type($filepath);
    }
    
    /**
     * Format file size for display
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    public static function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Validate file type against allowed types
     * @param string $mimeType File mime type
     * @param array $allowedTypes Array of allowed mime types
     * @return bool Validation result
     */
    public static function isAllowedFileType($mimeType, $allowedTypes) {
        return in_array($mimeType, $allowedTypes);
    }
    
    /**
     * Create a directory if it doesn't exist
     * @param string $path Directory path
     * @param int $permissions Directory permissions (default: 0777)
     * @return bool Success status
     */
    public static function createDirectory($path, $permissions = 0777) {
        if (!file_exists($path)) {
            return mkdir($path, $permissions, true);
        }
        return true;
    }
    
    /**
     * Generate pagination metadata
     * @param int $total Total number of items
     * @param int $limit Items per page
     * @param int $offset Current offset
     * @return array Pagination metadata
     */
    public static function getPaginationMetadata($total, $limit, $offset) {
        $currentPage = floor($offset / $limit) + 1;
        $totalPages = ceil($total / $limit);
        
        return [
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'has_more' => $currentPage < $totalPages
        ];
    }
    
    /**
     * Format API response
     * @param mixed $data Response data
     * @param string $message Response message
     * @param bool $success Success status
     * @param array $meta Additional metadata
     * @return array Formatted response
     */
    public static function formatApiResponse($data, $message = '', $success = true, $meta = []) {
        return [
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'meta' => $meta
        ];
    }
    
    /**
     * Format error response
     * @param string $message Error message
     * @param array $details Error details
     * @param int $code Error code
     * @return array Formatted error response
     */
    public static function formatErrorResponse($message, $details = [], $code = 0) {
        return [
            'success' => false,
            'error' => [
                'message' => $message,
                'details' => $details,
                'code' => $code
            ]
        ];
    }
    
    /**
     * Validate email address
     * @param string $email Email address
     * @return bool Validation result
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Generate a random password
     * @param int $length Password length
     * @return string Generated password
     */
    public static function generateRandomPassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
    
    /**
     * Check if string contains HTML
     * @param string $string String to check
     * @return bool Result
     */
    public static function containsHTML($string) {
        return $string !== strip_tags($string);
    }
    
    /**
     * Sanitize HTML content
     * @param string $content HTML content
     * @return string Sanitized content
     */
    public static function sanitizeHTML($content) {
        return htmlspecialchars($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Convert array to CSV string
     * @param array $data Array of data
     * @param array $headers CSV headers
     * @return string CSV content
     */
    public static function arrayToCSV($data, $headers = []) {
        $output = fopen('php://temp', 'r+');
        
        if (!empty($headers)) {
            fputcsv($output, $headers);
        }
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}
