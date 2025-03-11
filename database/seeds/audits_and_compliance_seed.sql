-- Insert sample compliance standards
INSERT INTO compliance_standards (name, description, version, effective_date) VALUES
('ISO 27001', 'Information Security Management System Standard', '2013', '2013-10-01'),
('GDPR', 'General Data Protection Regulation', '2016/679', '2018-05-25'),
('PCI DSS', 'Payment Card Industry Data Security Standard', '3.2.1', '2018-05-01'),
('HIPAA', 'Health Insurance Portability and Accountability Act', '2', '2003-04-14');

-- Insert sample compliance requirements
INSERT INTO compliance_requirements (standard_id, requirement_code, description, category, priority) VALUES
(1, 'A.5.1.1', 'Information Security Policies', 'Security Policy', 'high'),
(1, 'A.6.1.1', 'Information Security Roles and Responsibilities', 'Organization', 'high'),
(2, 'Art-5', 'Principles relating to processing of personal data', 'Data Processing', 'critical'),
(2, 'Art-7', 'Conditions for consent', 'Consent', 'high'),
(3, 'Req-1', 'Install and maintain a firewall configuration', 'Network Security', 'critical'),
(4, '164.308', 'Administrative safeguards', 'Security', 'high');

-- Insert sample audits
INSERT INTO audits (title, description, user_id, status, type, start_date, end_date) VALUES
(
    'Annual Security Assessment 2023',
    'Comprehensive review of internal security controls and procedures',
    1,
    'completed',
    'internal',
    '2023-01-15',
    '2023-02-15'
),
(
    'GDPR Compliance Review Q2 2023',
    'Assessment of GDPR compliance status across all departments',
    4,
    'in_progress',
    'compliance',
    '2023-04-01',
    NULL
);

-- Insert sample audit comments
INSERT INTO audit_comments (audit_id, user_id, comment) VALUES
(1, 1, 'Initial findings reviewed with department heads'),
(1, 4, 'Compliance implications assessed - additional controls needed');

-- Insert sample compliance assessments
INSERT INTO compliance_assessments (requirement_id, audit_id, status, evidence, assessed_by) VALUES
(1, 1, 'compliant', 'Security policies documented and up to date', 1),
(2, 1, 'partially_compliant', 'Some roles need clarification', 1),
(3, 2, 'in_progress', 'Data processing review ongoing', 4);
