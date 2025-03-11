<?php
namespace App\Middleware;

class Validation {
    /**
     * Validate and sanitize input data
     * @param array $data Input data to validate
     * @param array $rules Validation rules
     * @return array Array with validation status and errors/sanitized data
     */
    public static function validate($data, $rules) {
        $errors = [];
        $sanitized = [];
        
        foreach ($rules as $field => $fieldRules) {
            // Skip if field is not required and not present
            if (!isset($data[$field]) && !in_array('required', $fieldRules)) {
                continue;
            }
            
            $value = $data[$field] ?? null;
            
            // Check required fields
            if (in_array('required', $fieldRules) && empty($value)) {
                $errors[$field] = "The $field field is required";
                continue;
            }
            
            // Skip further validation if field is empty and not required
            if (empty($value)) {
                continue;
            }
            
            // Apply validation rules
            foreach ($fieldRules as $rule) {
                if ($rule === 'required') continue;
                
                switch ($rule) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = "Invalid email format";
                        }
                        $sanitized[$field] = filter_var($value, FILTER_SANITIZE_EMAIL);
                        break;
                        
                    case 'string':
                        if (!is_string($value)) {
                            $errors[$field] = "The $field must be a string";
                        }
                        $sanitized[$field] = htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
                        break;
                        
                    case 'number':
                        if (!is_numeric($value)) {
                            $errors[$field] = "The $field must be a number";
                        }
                        $sanitized[$field] = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT);
                        break;
                        
                    case 'date':
                        $date = strtotime($value);
                        if (!$date) {
                            $errors[$field] = "Invalid date format for $field";
                        }
                        $sanitized[$field] = date('Y-m-d', $date);
                        break;
                        
                    default:
                        if (strpos($rule, 'min:') === 0) {
                            $min = substr($rule, 4);
                            if (strlen($value) < $min) {
                                $errors[$field] = "The $field must be at least $min characters";
                            }
                        }
                        else if (strpos($rule, 'max:') === 0) {
                            $max = substr($rule, 4);
                            if (strlen($value) > $max) {
                                $errors[$field] = "The $field must not exceed $max characters";
                            }
                        }
                        break;
                }
            }
        }
        
        return [
            'isValid' => empty($errors),
            'errors' => $errors,
            'sanitized' => $sanitized
        ];
    }
    
    /**
     * Common validation rules for user registration
     * @return array Validation rules
     */
    public static function getUserRegistrationRules() {
        return [
            'username' => ['required', 'string', 'min:3', 'max:50'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8'],
            'role' => ['string']
        ];
    }
    
    /**
     * Common validation rules for user login
     * @return array Validation rules
     */
    public static function getUserLoginRules() {
        return [
            'email' => ['required', 'email'],
            'password' => ['required']
        ];
    }
    
    /**
     * Common validation rules for audit creation
     * @return array Validation rules
     */
    public static function getAuditRules() {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'date' => ['required', 'date'],
            'status' => ['required', 'string'],
            'type' => ['required', 'string']
        ];
    }
    
    /**
     * Common validation rules for report creation
     * @return array Validation rules
     */
    public static function getReportRules() {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'audit_id' => ['required', 'number'],
            'status' => ['required', 'string']
        ];
    }
    
    /**
     * Sanitize SQL query parameters
     * @param string $value Value to sanitize
     * @return string Sanitized value
     */
    public static function sanitizeSQL($value) {
        return addslashes(htmlspecialchars(strip_tags($value)));
    }
    
    /**
     * Validate and sanitize JSON input
     * @param string $json JSON string to validate
     * @return array|false Decoded JSON data or false on failure
     */
    public static function validateJSON($json) {
        if (!is_string($json)) {
            return false;
        }
        
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        
        return $data;
    }
}
