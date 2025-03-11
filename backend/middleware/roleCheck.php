<?php
namespace App\Middleware;

use App\Middleware\Auth;

class RoleCheck {
    /**
     * Check if user has required role
     * @param string|array $requiredRoles Single role or array of roles
     * @return bool Authorization status
     */
    public static function hasRole($requiredRoles) {
        // First check if user is authenticated
        if (!Auth::isAuthenticated()) {
            return false;
        }

        $user = Auth::getCurrentUser();
        if (!$user) {
            return false;
        }

        // Convert single role to array for consistent checking
        if (!is_array($requiredRoles)) {
            $requiredRoles = [$requiredRoles];
        }

        // Check if user's role matches any of the required roles
        return in_array($user['role'], $requiredRoles);
    }

    /**
     * Middleware to verify admin access
     * @return bool Authorization status
     */
    public static function isAdmin() {
        if (!self::hasRole('admin')) {
            http_response_code(403);
            echo json_encode(['error' => 'Access forbidden. Admin privileges required.']);
            return false;
        }
        return true;
    }

    /**
     * Middleware to verify user access (either regular user or admin)
     * @return bool Authorization status
     */
    public static function isUser() {
        if (!self::hasRole(['user', 'admin'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Access forbidden. User privileges required.']);
            return false;
        }
        return true;
    }

    /**
     * Check if user has access to specific resource
     * @param int $resourceUserId User ID associated with the resource
     * @return bool Authorization status
     */
    public static function canAccessResource($resourceUserId) {
        $user = Auth::getCurrentUser();
        
        // Admins can access all resources
        if ($user['role'] === 'admin') {
            return true;
        }

        // Users can only access their own resources
        if ($user['id'] === $resourceUserId) {
            return true;
        }

        http_response_code(403);
        echo json_encode(['error' => 'Access forbidden. You do not have permission to access this resource.']);
        return false;
    }

    /**
     * Middleware to verify specific permission
     * @param string $permission Permission to check
     * @return bool Authorization status
     */
    public static function hasPermission($permission) {
        // Define permission mappings
        $permissions = [
            'manage_users' => ['admin'],
            'view_reports' => ['admin', 'user'],
            'create_audits' => ['admin', 'user'],
            'manage_compliance' => ['admin']
        ];

        if (!isset($permissions[$permission])) {
            error_log("Undefined permission check: $permission");
            return false;
        }

        if (!self::hasRole($permissions[$permission])) {
            http_response_code(403);
            echo json_encode(['error' => "Access forbidden. Required permission: $permission"]);
            return false;
        }
        return true;
    }
}
