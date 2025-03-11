<?php
namespace App\Models;

class User {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Create a new user
     * @param array $data User data (username, email, password)
     * @return array|false User data or false on failure
     */
    public function create($data) {
        try {
            $query = "INSERT INTO users (username, email, password, role, created_at) 
                     VALUES (:username, :email, :password, :role, NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role' => $data['role'] ?? 'user'
            ]);
            
            return $this->getById($this->db->lastInsertId());
        } catch (\PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user by ID
     * @param int $id User ID
     * @return array|false User data or false if not found
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT id, username, email, role, created_at FROM users WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log("Error fetching user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user by email
     * @param string $email User email
     * @return array|false User data or false if not found
     */
    public function getByEmail($email) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log("Error fetching user by email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update user details
     * @param int $id User ID
     * @param array $data Updated user data
     * @return bool Success status
     */
    public function update($id, $data) {
        try {
            $fields = [];
            $params = ['id' => $id];
            
            foreach ($data as $key => $value) {
                if (in_array($key, ['username', 'email', 'role'])) {
                    $fields[] = "$key = :$key";
                    $params[$key] = $value;
                }
            }
            
            if (isset($data['password'])) {
                $fields[] = "password = :password";
                $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $query = "UPDATE users SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($query);
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            error_log("Error updating user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a user
     * @param int $id User ID
     * @return bool Success status
     */
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (\PDOException $e) {
            error_log("Error deleting user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify user password
     * @param string $password Password to verify
     * @param string $hash Stored password hash
     * @return bool Password validity
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Get all users (with optional pagination)
     * @param int $limit Limit of records
     * @param int $offset Offset for pagination
     * @return array List of users
     */
    public function getAllUsers($limit = 10, $offset = 0) {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, username, email, role, created_at 
                 FROM users 
                 ORDER BY created_at DESC 
                 LIMIT :limit OFFSET :offset"
            );
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Error fetching users: " . $e->getMessage());
            return [];
        }
    }
}
