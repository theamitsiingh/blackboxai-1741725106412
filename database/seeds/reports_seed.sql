-- Insert sample reports
INSERT INTO reports (title, content, audit_id, user_id, status, submission_date, review_date, reviewer_id, review_comments) VALUES
(
    'Annual Security Assessment Report 2023',
    'Executive Summary:\n
    The annual security assessment revealed several areas requiring attention, particularly in system updates and log management.\n\n
    Key Findings:\n
    1. Outdated Software: Multiple systems running deprecated versions\n
    2. Log Management: Inconsistent logging practices\n
    3. Access Controls: Need for improved documentation\n\n
    Recommendations:\n
    1. Implement automated system updates\n
    2. Centralize log management\n
    3. Review and update access control documentation',
    1, -- linked to Annual Security Assessment audit
    3, -- created by auditor1
    'approved',
    '2023-02-20 10:00:00',
    '2023-02-25 14:30:00',
    1, -- reviewed by admin
    'Comprehensive report with actionable recommendations. Approved for distribution.'
),
(
    'GDPR Compliance Interim Report Q2 2023',
    'Preliminary Findings:\n
    Current assessment of GDPR compliance shows progress in several key areas while highlighting needs for improvement.\n\n
    Areas Reviewed:\n
    1. Data Processing Activities\n
    2. Consent Management\n
    3. Data Subject Rights Procedures\n\n
    Initial Recommendations:\n
    1. Update data processing documentation\n
    2. Enhance consent tracking mechanisms\n
    3. Streamline data subject request handling',
    2, -- linked to GDPR Compliance Review
    4, -- created by compliance officer
    'submitted',
    '2023-04-15 09:00:00',
    NULL,
    NULL,
    NULL
);

-- Insert sample report attachments
INSERT INTO report_attachments (report_id, file_name, file_path, file_type, file_size, uploaded_by) VALUES
(1, 'security_scan_results.pdf', '/attachments/2023/02/security_scan_results.pdf', 'application/pdf', 2048576, 3),
(1, 'system_versions.xlsx', '/attachments/2023/02/system_versions.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 1048576, 3),
(2, 'gdpr_checklist.pdf', '/attachments/2023/04/gdpr_checklist.pdf', 'application/pdf', 1548576, 4);
