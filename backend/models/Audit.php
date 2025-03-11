<?php
namespace App\Models;

class Audit {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Create a new audit
     * @param array $data Audit data
     * @return array|false Created audit data or false on failure
     */
    public function create($data) {
        try {
            $query = "INSERT INTO audits (title, description, user_id, status, type, start_date, end_date, findings, recommendations) 
                     VALUES (:title, :description, :user_id, :status, :type, :start_date, :end_date, :findings, :recommendations)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                'title' => $data['title'],
                'description' => $data['description'],
                'user_id' => $data['user_id'],
                'status' => $data['status'] ?? 'pending',
                'type' => $data['type'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'findings' => $data['findings'] ?? null,
                'recommendations' => $data['recommendations'] ?? null
            ]);
            
            return $this->getById($this->db->lastInsertId());
        } catch (\PDOException $e) {
            error_log("Error creating audit: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get audit by ID with related data
     * @param int $id Audit ID
     * @return array|false Audit data or false if not found
     */
    public function getById($id) {
        try {
            // Get main audit data
            $query = "SELECT a.*, u.username as created_by_username 
                     FROM audits a 
                     LEFT JOIN users u ON a.user_id = u.id 
                     WHERE a.id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute(['id' => $id]);
            $audit = $stmt->fetch();
            
            if (!$audit) {
                return false;
            }
            
            // Get comments
            $audit['comments'] = $this->getAuditComments($id);
            
            // Get attachments
            $audit['attachments'] = $this->getAuditAttachments($id);
            
            // Get compliance assessments
            $audit['compliance_assessments'] = $this->getComplianceAssessments($id);
            
            return $audit;
        } catch (\PDOException $e) {
            error_log("Error fetching audit: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update audit details
     * @param int $id Audit ID
     * @param array $data Updated audit data
     * @return bool Success status
     */
    public function update($id, $data) {
        try {
            $fields = [];
            $params = ['id' => $id];
            
            $allowedFields = [
                'title', 'description', 'status', 'type', 
                'start_date', 'end_date', 'findings', 'recommendations'
            ];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $fields[] = "$key = :$key";
                    $params[$key] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $query = "UPDATE audits SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $this->db->prepare($query);
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            error_log("Error updating audit: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get audit comments
     * @param int $auditId Audit ID
     * @return array Comments
     */
    private function getAuditComments($auditId) {
        try {
            $query = "SELECT ac.*, u.username 
                     FROM audit_comments ac 
                     LEFT JOIN users u ON ac.user_id = u.id 
                     WHERE ac.audit_id = :audit_id 
                     ORDER BY ac.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute(['audit_id' => $auditId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Error fetching audit comments: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get audit attachments
     * @param int $auditId Audit ID
     * @return array Attachments
     */
    private function getAuditAttachments($auditId) {
        try {
            $query = "SELECT aa.*, u.username as uploaded_by_username 
                     FROM audit_attachments aa 
                     LEFT JOIN users u ON aa.uploaded_by = u.id 
                     WHERE aa.audit_id = :audit_id 
                     ORDER BY aa.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute(['audit_id' => $auditId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Error fetching audit attachments: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get compliance assessments for audit
     * @param int $auditId Audit ID
     * @return array Compliance assessments
     */
    private function getComplianceAssessments($auditId) {
        try {
            $query = "SELECT ca.*, cr.requirement_code, cr.description as requirement_description,
                     cs.name as standard_name, u.username as assessed_by_username 
                     FROM compliance_assessments ca 
                     LEFT JOIN compliance_requirements cr ON ca.requirement_id = cr.id 
                     LEFT JOIN compliance_standards cs ON cr.standard_id = cs.id 
                     LEFT JOIN users u ON ca.assessed_by = u.id 
                     WHERE ca.audit_id = :audit_id 
                     ORDER BY cs.name, cr.requirement_code";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute(['audit_id' => $auditId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Error fetching compliance assessments: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get audits with optional filtering and pagination
     * @param array $filters Filter conditions
     * @param int $limit Records per page
     * @param int $offset Pagination offset
     * @return array Audits list
     */
    public function getAudits($filters = [], $limit = 10, $offset = 0) {
        try {
            $where = [];
            $params = [];
            
            if (!empty($filters['user_id'])) {
                $where[] = "a.user_id = :user_id";
                $params['user_id'] = $filters['user_id'];
            }
            
            if (!empty($filters['status'])) {
                $where[] = "a.status = :status";
                $params['status'] = $filters['status'];
            }
            
            if (!empty($filters['type'])) {
                $where[] = "a.type = :type";
                $params['type'] = $filters['type'];
            }
            
            $whereClause = empty($where) ? "" : "WHERE " . implode(" AND ", $where);
            
            $query = "SELECT a.*, u.username as created_by_username 
                     FROM audits a 
                     LEFT JOIN users u ON a.user_id = u.id 
                     $whereClause 
                     ORDER BY a.created_at DESC 
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            
            // Bind pagination parameters
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            
            // Bind filter parameters
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Error fetching audits: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Add a comment to an audit
     * @param array $data Comment data
     * @return array|false Created comment or false on failure
     */
    public function addComment($data) {
        try {
            $query = "INSERT INTO audit_comments (audit_id, user_id, comment) 
                     VALUES (:audit_id, :user_id, :comment)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                'audit_id' => $data['audit_id'],
                'user_id' => $data['user_id'],
                'comment' => $data['comment']
            ]);
            
            // Return the created comment with username
            $commentId = $this->db->lastInsertId();
            $query = "SELECT ac.*, u.username 
                     FROM audit_comments ac 
                     LEFT JOIN users u ON ac.user_id = u.id 
                     WHERE ac.id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute(['id' => $commentId]);
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log("Error adding audit comment: " . $e->getMessage());
            return false;
        }
    }
}
