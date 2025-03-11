<?php
namespace App\Models;

class Report {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Create a new report
     * @param array $data Report data
     * @return array|false Created report data or false on failure
     */
    public function create($data) {
        try {
            $query = "INSERT INTO reports (title, content, audit_id, user_id, status, submission_date) 
                     VALUES (:title, :content, :audit_id, :user_id, :status, :submission_date)";
            
            $params = [
                'title' => $data['title'],
                'content' => $data['content'],
                'audit_id' => $data['audit_id'] ?? null,
                'user_id' => $data['user_id'],
                'status' => $data['status'] ?? 'draft',
                'submission_date' => $data['status'] === 'submitted' ? date('Y-m-d H:i:s') : null
            ];
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $this->getById($this->db->lastInsertId());
        } catch (\PDOException $e) {
            error_log("Error creating report: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get report by ID with related data
     * @param int $id Report ID
     * @return array|false Report data or false if not found
     */
    public function getById($id) {
        try {
            // Get main report data with related user information
            $query = "SELECT r.*, 
                     u1.username as created_by_username,
                     u2.username as reviewer_username,
                     a.title as audit_title
                     FROM reports r 
                     LEFT JOIN users u1 ON r.user_id = u1.id 
                     LEFT JOIN users u2 ON r.reviewer_id = u2.id 
                     LEFT JOIN audits a ON r.audit_id = a.id 
                     WHERE r.id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute(['id' => $id]);
            $report = $stmt->fetch();
            
            if (!$report) {
                return false;
            }
            
            // Get attachments
            $report['attachments'] = $this->getReportAttachments($id);
            
            return $report;
        } catch (\PDOException $e) {
            error_log("Error fetching report: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update report details
     * @param int $id Report ID
     * @param array $data Updated report data
     * @return bool Success status
     */
    public function update($id, $data) {
        try {
            $fields = [];
            $params = ['id' => $id];
            
            $allowedFields = [
                'title', 'content', 'status', 'review_comments',
                'reviewer_id', 'review_date'
            ];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $fields[] = "$key = :$key";
                    $params[$key] = $value;
                }
            }
            
            // Handle status changes and associated dates
            if (isset($data['status'])) {
                if ($data['status'] === 'submitted') {
                    $fields[] = "submission_date = NOW()";
                } elseif (in_array($data['status'], ['approved', 'rejected'])) {
                    $fields[] = "review_date = NOW()";
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $query = "UPDATE reports SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $this->db->prepare($query);
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            error_log("Error updating report: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get report attachments
     * @param int $reportId Report ID
     * @return array Attachments
     */
    private function getReportAttachments($reportId) {
        try {
            $query = "SELECT ra.*, u.username as uploaded_by_username 
                     FROM report_attachments ra 
                     LEFT JOIN users u ON ra.uploaded_by = u.id 
                     WHERE ra.report_id = :report_id 
                     ORDER BY ra.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute(['report_id' => $reportId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Error fetching report attachments: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Add attachment to report
     * @param array $data Attachment data
     * @return array|false Created attachment or false on failure
     */
    public function addAttachment($data) {
        try {
            $query = "INSERT INTO report_attachments 
                     (report_id, file_name, file_path, file_type, file_size, uploaded_by) 
                     VALUES (:report_id, :file_name, :file_path, :file_type, :file_size, :uploaded_by)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                'report_id' => $data['report_id'],
                'file_name' => $data['file_name'],
                'file_path' => $data['file_path'],
                'file_type' => $data['file_type'],
                'file_size' => $data['file_size'],
                'uploaded_by' => $data['uploaded_by']
            ]);
            
            return $this->getAttachmentById($this->db->lastInsertId());
        } catch (\PDOException $e) {
            error_log("Error adding report attachment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get attachment by ID
     * @param int $id Attachment ID
     * @return array|false Attachment data or false if not found
     */
    private function getAttachmentById($id) {
        try {
            $query = "SELECT ra.*, u.username as uploaded_by_username 
                     FROM report_attachments ra 
                     LEFT JOIN users u ON ra.uploaded_by = u.id 
                     WHERE ra.id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute(['id' => $id]);
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log("Error fetching attachment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get reports with optional filtering and pagination
     * @param array $filters Filter conditions
     * @param int $limit Records per page
     * @param int $offset Pagination offset
     * @return array Reports list
     */
    public function getReports($filters = [], $limit = 10, $offset = 0) {
        try {
            $where = [];
            $params = [];
            
            if (!empty($filters['user_id'])) {
                $where[] = "r.user_id = :user_id";
                $params['user_id'] = $filters['user_id'];
            }
            
            if (!empty($filters['status'])) {
                $where[] = "r.status = :status";
                $params['status'] = $filters['status'];
            }
            
            if (!empty($filters['audit_id'])) {
                $where[] = "r.audit_id = :audit_id";
                $params['audit_id'] = $filters['audit_id'];
            }
            
            $whereClause = empty($where) ? "" : "WHERE " . implode(" AND ", $where);
            
            $query = "SELECT r.*, 
                     u1.username as created_by_username,
                     u2.username as reviewer_username,
                     a.title as audit_title
                     FROM reports r 
                     LEFT JOIN users u1 ON r.user_id = u1.id 
                     LEFT JOIN users u2 ON r.reviewer_id = u2.id 
                     LEFT JOIN audits a ON r.audit_id = a.id 
                     $whereClause 
                     ORDER BY r.created_at DESC 
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
            error_log("Error fetching reports: " . $e->getMessage());
            return [];
        }
    }
}
