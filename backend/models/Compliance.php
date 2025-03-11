<?php
namespace App\Models;

class Compliance {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get compliance standard by ID
     * @param int $id Standard ID
     * @return array|false Standard data or false if not found
     */
    public function getStandardById($id) {
        try {
            $query = "SELECT * FROM compliance_standards WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['id' => $id]);
            
            $standard = $stmt->fetch();
            if ($standard) {
                $standard['requirements'] = $this->getRequirementsByStandardId($id);
            }
            
            return $standard;
        } catch (\PDOException $e) {
            error_log("Error fetching compliance standard: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all compliance standards
     * @return array List of standards
     */
    public function getAllStandards() {
        try {
            $query = "SELECT * FROM compliance_standards ORDER BY name";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Error fetching compliance standards: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get requirements by standard ID
     * @param int $standardId Standard ID
     * @return array List of requirements
     */
    public function getRequirementsByStandardId($standardId) {
        try {
            $query = "SELECT * FROM compliance_requirements 
                     WHERE standard_id = :standard_id 
                     ORDER BY requirement_code";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute(['standard_id' => $standardId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Error fetching compliance requirements: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create compliance assessment
     * @param array $data Assessment data
     * @return array|false Created assessment or false on failure
     */
    public function createAssessment($data) {
        try {
            $query = "INSERT INTO compliance_assessments 
                     (requirement_id, audit_id, status, evidence, notes, assessed_by) 
                     VALUES (:requirement_id, :audit_id, :status, :evidence, :notes, :assessed_by)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                'requirement_id' => $data['requirement_id'],
                'audit_id' => $data['audit_id'],
                'status' => $data['status'],
                'evidence' => $data['evidence'] ?? null,
                'notes' => $data['notes'] ?? null,
                'assessed_by' => $data['assessed_by']
            ]);
            
            return $this->getAssessmentById($this->db->lastInsertId());
        } catch (\PDOException $e) {
            error_log("Error creating compliance assessment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update compliance assessment
     * @param int $id Assessment ID
     * @param array $data Updated assessment data
     * @return bool Success status
     */
    public function updateAssessment($id, $data) {
        try {
            $fields = [];
            $params = ['id' => $id];
            
            $allowedFields = ['status', 'evidence', 'notes'];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $fields[] = "$key = :$key";
                    $params[$key] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $query = "UPDATE compliance_assessments 
                     SET " . implode(', ', $fields) . " 
                     WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            error_log("Error updating compliance assessment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get assessment by ID
     * @param int $id Assessment ID
     * @return array|false Assessment data or false if not found
     */
    public function getAssessmentById($id) {
        try {
            $query = "SELECT ca.*, 
                     cr.requirement_code, cr.description as requirement_description,
                     cs.name as standard_name, cs.version as standard_version,
                     u.username as assessed_by_username
                     FROM compliance_assessments ca 
                     LEFT JOIN compliance_requirements cr ON ca.requirement_id = cr.id 
                     LEFT JOIN compliance_standards cs ON cr.standard_id = cs.id 
                     LEFT JOIN users u ON ca.assessed_by = u.id 
                     WHERE ca.id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute(['id' => $id]);
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log("Error fetching compliance assessment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get assessments by audit ID
     * @param int $auditId Audit ID
     * @return array List of assessments
     */
    public function getAssessmentsByAuditId($auditId) {
        try {
            $query = "SELECT ca.*, 
                     cr.requirement_code, cr.description as requirement_description,
                     cs.name as standard_name, cs.version as standard_version,
                     u.username as assessed_by_username
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
            error_log("Error fetching audit compliance assessments: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get compliance summary by audit
     * @param int $auditId Audit ID
     * @return array Summary statistics
     */
    public function getComplianceSummary($auditId) {
        try {
            $query = "SELECT 
                     cs.name as standard_name,
                     COUNT(*) as total_requirements,
                     SUM(CASE WHEN ca.status = 'compliant' THEN 1 ELSE 0 END) as compliant,
                     SUM(CASE WHEN ca.status = 'non_compliant' THEN 1 ELSE 0 END) as non_compliant,
                     SUM(CASE WHEN ca.status = 'partially_compliant' THEN 1 ELSE 0 END) as partially_compliant,
                     SUM(CASE WHEN ca.status = 'not_applicable' THEN 1 ELSE 0 END) as not_applicable
                     FROM compliance_assessments ca 
                     JOIN compliance_requirements cr ON ca.requirement_id = cr.id 
                     JOIN compliance_standards cs ON cr.standard_id = cs.id 
                     WHERE ca.audit_id = :audit_id 
                     GROUP BY cs.name";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute(['audit_id' => $auditId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Error generating compliance summary: " . $e->getMessage());
            return [];
        }
    }
}
