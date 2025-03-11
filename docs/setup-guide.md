# Audit and Compliance Management System - Setup Guide

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer (PHP package manager)
- Web server (Apache/Nginx)

## Installation Steps

### 1. Clone the Repository

```bash
git clone <repository-url>
cd audit-compliance-system
```

### 2. Install Dependencies

Navigate to the backend directory and install PHP dependencies:

```bash
cd backend
composer install
```

### 3. Database Setup

1. Create a new MySQL database:

```sql
CREATE DATABASE audit_compliance_db;
```

2. Import the database schema:

```bash
mysql -u your_username -p audit_compliance_db < database/schema.sql
```

This will:
- Create all necessary tables
- Set up foreign key relationships
- Create required indexes
- Import sample data (if needed)

### 4. Environment Configuration

1. Copy the example environment file:

```bash
cp backend/.env.example backend/.env
```

2. Update the `.env` file with your configuration:

```env
DB_HOST=localhost
DB_NAME=audit_compliance_db
DB_USER=your_username
DB_PASS=your_password
JWT_SECRET=your-secure-jwt-secret-key
DEBUG=true  # Set to false in production
```

### 5. Directory Permissions

Set appropriate permissions for log and upload directories:

```bash
chmod -R 755 backend/logs
chmod -R 755 backend/uploads
```

### 6. Web Server Configuration

#### Apache Configuration

Create or update your Apache virtual host configuration:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/project/backend
    
    <Directory /path/to/project/backend>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

#### Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/project/backend;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 7. Initial Setup

1. Create the admin user:
   - The default admin credentials are:
     - Email: admin@example.com
     - Password: Admin123!
   - Change these credentials immediately after first login

2. Verify the installation:
   - Visit `http://your-domain.com/api/health`
   - You should see a JSON response indicating the system is healthy

## API Documentation

The API documentation is available at `docs/api-docs.md`. It includes:
- Available endpoints
- Request/response formats
- Authentication requirements
- Example requests

## Security Considerations

1. In production:
   - Set DEBUG=false in .env
   - Use HTTPS
   - Change default credentials
   - Set secure values for JWT_SECRET
   - Configure proper file permissions

2. Regular maintenance:
   - Monitor log files
   - Backup database regularly
   - Keep dependencies updated
   - Review security settings

## Troubleshooting

### Common Issues

1. Database Connection Errors:
   - Verify database credentials in .env
   - Ensure MySQL is running
   - Check database user permissions

2. File Permission Issues:
   - Verify web server user has appropriate permissions
   - Check log directory permissions
   - Check upload directory permissions

3. API Errors:
   - Check error logs in backend/logs
   - Verify API endpoint URLs
   - Confirm authentication tokens

### Getting Help

If you encounter issues:
1. Check the logs in backend/logs
2. Review the API documentation
3. Contact system administrator

## Development Guidelines

1. Code Style:
   - Follow PSR-4 autoloading standards
   - Use meaningful variable and function names
   - Add comments for complex logic
   - Follow PHP 7.4+ type declarations

2. Testing:
   - Write unit tests for new features
   - Test API endpoints thoroughly
   - Verify database migrations

3. Version Control:
   - Create feature branches
   - Write meaningful commit messages
   - Review code before merging

## Maintenance

Regular maintenance tasks:

1. Database:
   - Regular backups
   - Optimize tables
   - Monitor performance

2. Logs:
   - Rotate logs regularly
   - Monitor disk space
   - Archive old logs

3. Updates:
   - Keep PHP packages updated
   - Apply security patches
   - Update documentation

## Support

For additional support:
- Review the documentation
- Check the issue tracker
- Contact the development team

## License

[Include license information here]
