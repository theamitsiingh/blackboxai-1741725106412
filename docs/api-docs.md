# Audit and Compliance Management System - API Documentation

## Authentication

All API requests (except login/signup) require authentication using JWT tokens.

### Headers

```
Authorization: Bearer <jwt_token>
```

### Authentication Endpoints

#### Login
```
POST /api/auth/login

Request:
{
    "email": "string",
    "password": "string"
}

Response:
{
    "success": true,
    "token": "string",
    "user": {
        "id": number,
        "username": "string",
        "email": "string",
        "role": "string"
    }
}
```

#### Signup
```
POST /api/auth/signup

Request:
{
    "username": "string",
    "email": "string",
    "password": "string"
}

Response:
{
    "success": true,
    "token": "string",
    "user": {
        "id": number,
        "username": "string",
        "email": "string",
        "role": "string"
    }
}
```

## Admin Endpoints

### Audits

#### List Audits
```
GET /api/admin/audits
Query Parameters:
- status: string (optional)
- type: string (optional)
- user_id: number (optional)
- limit: number (optional, default: 10)
- offset: number (optional, default: 0)

Response:
{
    "data": [
        {
            "id": number,
            "title": "string",
            "description": "string",
            "status": "string",
            "type": "string",
            "user_id": number,
            "created_by_username": "string",
            "start_date": "string",
            "end_date": "string",
            "created_at": "string"
        }
    ]
}
```

#### Get Audit
```
GET /api/admin/audits?id={id}

Response:
{
    "data": {
        "id": number,
        "title": "string",
        "description": "string",
        "status": "string",
        "type": "string",
        "user_id": number,
        "created_by_username": "string",
        "start_date": "string",
        "end_date": "string",
        "findings": "string",
        "recommendations": "string",
        "created_at": "string",
        "comments": [...],
        "attachments": [...],
        "compliance_assessments": [...]
    }
}
```

#### Create Audit
```
POST /api/admin/audits

Request:
{
    "title": "string",
    "description": "string",
    "type": "string",
    "start_date": "string",
    "end_date": "string" (optional)
}

Response:
{
    "data": {
        // Created audit object
    }
}
```

#### Update Audit
```
PUT /api/admin/audits?id={id}

Request:
{
    "status": "string",
    "findings": "string",
    "recommendations": "string"
}

Response:
{
    "data": {
        // Updated audit object
    }
}
```

### Reports

#### List Reports
```
GET /api/admin/reports
Query Parameters:
- status: string (optional)
- audit_id: number (optional)
- user_id: number (optional)
- limit: number (optional, default: 10)
- offset: number (optional, default: 0)

Response:
{
    "data": [
        {
            "id": number,
            "title": "string",
            "content": "string",
            "status": "string",
            "audit_id": number,
            "user_id": number,
            "created_by_username": "string",
            "created_at": "string"
        }
    ]
}
```

#### Get Report
```
GET /api/admin/reports?id={id}

Response:
{
    "data": {
        "id": number,
        "title": "string",
        "content": "string",
        "status": "string",
        "audit_id": number,
        "user_id": number,
        "created_by_username": "string",
        "reviewer_username": "string",
        "review_comments": "string",
        "created_at": "string",
        "attachments": [...]
    }
}
```

#### Review Report
```
PUT /api/admin/reports?id={id}

Request:
{
    "status": "string" (approved/rejected),
    "review_comments": "string"
}

Response:
{
    "data": {
        // Updated report object
    }
}
```

### Compliance

#### List Standards
```
GET /api/admin/compliance

Response:
{
    "data": [
        {
            "id": number,
            "name": "string",
            "description": "string",
            "version": "string",
            "effective_date": "string"
        }
    ]
}
```

#### Get Standard
```
GET /api/admin/compliance?standard_id={id}

Response:
{
    "data": {
        "id": number,
        "name": "string",
        "description": "string",
        "version": "string",
        "effective_date": "string",
        "requirements": [...]
    }
}
```

#### Create Assessment
```
POST /api/admin/compliance?action=create_assessment

Request:
{
    "requirement_id": number,
    "audit_id": number,
    "status": "string",
    "evidence": "string",
    "notes": "string"
}

Response:
{
    "data": {
        // Created assessment object
    }
}
```

#### Update Assessment
```
PUT /api/admin/compliance?assessment_id={id}

Request:
{
    "status": "string",
    "evidence": "string",
    "notes": "string"
}

Response:
{
    "data": {
        // Updated assessment object
    }
}
```

## User Endpoints

### Audits

#### List User Audits
```
GET /api/user/audits

Response:
{
    "data": [
        {
            // Audit objects for current user
        }
    ]
}
```

#### Get User Audit
```
GET /api/user/audits?id={id}

Response:
{
    "data": {
        // Audit object if user has access
    }
}
```

#### Add Comment
```
POST /api/user/audits?id={id}&action=comment

Request:
{
    "comment": "string"
}

Response:
{
    "data": {
        // Created comment object
    }
}
```

### Reports

#### List User Reports
```
GET /api/user/reports

Response:
{
    "data": [
        {
            // Report objects for current user
        }
    ]
}
```

#### Create Report
```
POST /api/user/reports

Request:
{
    "title": "string",
    "content": "string",
    "audit_id": number
}

Response:
{
    "data": {
        // Created report object
    }
}
```

#### Update Report
```
PUT /api/user/reports?id={id}

Request:
{
    "title": "string",
    "content": "string"
}

Response:
{
    "data": {
        // Updated report object
    }
}
```

#### Submit Report
```
PUT /api/user/reports?id={id}

Request:
{
    "submit": true
}

Response:
{
    "data": {
        // Updated report object with submitted status
    }
}
```

## Error Responses

All endpoints return error responses in the following format:

```json
{
    "success": false,
    "error": {
        "message": "string",
        "details": {},
        "code": number
    }
}
```

Common error codes:
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 500: Internal Server Error

## File Upload Endpoints

### Report Attachments
```
POST /api/user/reports?id={id}&action=attachment
Content-Type: multipart/form-data

Request:
- file: File object

Response:
{
    "data": {
        "id": number,
        "file_name": "string",
        "file_path": "string",
        "file_type": "string",
        "file_size": number,
        "uploaded_by": number,
        "created_at": "string"
    }
}
```

## Pagination

Endpoints that return lists support pagination using:
- limit: Number of items per page
- offset: Number of items to skip

Response includes metadata:
```json
{
    "data": [...],
    "meta": {
        "total": number,
        "per_page": number,
        "current_page": number,
        "total_pages": number,
        "has_more": boolean
    }
}
```

## Rate Limiting

API requests are limited to:
- 100 requests per minute for authenticated users
- 20 requests per minute for unauthenticated users

Rate limit headers:
```
X-RateLimit-Limit: number
X-RateLimit-Remaining: number
X-RateLimit-Reset: number
