-- Audit and Compliance Management System Database Schema

-- Drop tables if they exist (in correct order due to foreign key constraints)
DROP TABLE IF EXISTS report_attachments;
DROP TABLE IF EXISTS audit_attachments;
DROP TABLE IF EXISTS audit_comments;
DROP TABLE IF EXISTS compliance_assessments;
DROP TABLE IF EXISTS compliance_requirements;
DROP TABLE IF EXISTS compliance_standards;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS audits;
DROP TABLE IF EXISTS users;

-- Include all migrations in the correct order
SOURCE migrations/create_users_table.sql;
SOURCE migrations/create_audits_table.sql;
SOURCE migrations/create_reports_and_compliance_tables.sql;

-- Include seed data
SOURCE seeds/users_seed.sql;
SOURCE seeds/audits_and_compliance_seed.sql;
SOURCE seeds/reports_seed.sql;

-- Database Schema Overview:
/*
Database Name: audit_compliance_db

Tables:
1. users
   - Primary table for user management
   - Stores user credentials and roles
   - Referenced by: audits, reports, audit_comments, compliance_assessments

2. audits
   - Core table for audit records
   - Links to: users (creator), audit_comments, audit_attachments
   - Referenced by: reports, compliance_assessments

3. audit_attachments
   - Stores files related to audits
   - Links to: audits (parent), users (uploader)

4. audit_comments
   - Stores discussion threads for audits
   - Links to: audits (parent), users (commenter)

5. reports
   - Stores detailed audit reports
   - Links to: audits (parent), users (creator, reviewer)
   - Referenced by: report_attachments

6. report_attachments
   - Stores files related to reports
   - Links to: reports (parent), users (uploader)

7. compliance_standards
   - Stores compliance framework definitions
   - Referenced by: compliance_requirements

8. compliance_requirements
   - Stores specific requirements for each standard
   - Links to: compliance_standards (parent)
   - Referenced by: compliance_assessments

9. compliance_assessments
   - Stores compliance status for each requirement
   - Links to: compliance_requirements, audits, users (assessor)

Key Relationships:
- Each audit is created by a user
- Reports are linked to audits and created/reviewed by users
- Compliance assessments link requirements to audits
- Attachments and comments are linked to their parent records
- All actions are tracked with user references

Notes:
- All tables include created_at timestamps
- Most tables include updated_at timestamps with triggers
- Appropriate indexes are created for frequent queries
- Foreign key constraints ensure data integrity
- Enum types are used for status and type fields
*/
