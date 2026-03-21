# INFOTESS API v2 Documentation

## Overview
This is the new version 2 of the INFOTESS Student Data Management System API, built with modern PHP practices and optimized for Vercel deployment.

## Base URL
```
https://your-domain.vercel.app/api/v2
```

## Authentication
The API uses JWT (JSON Web Tokens) for authentication. Include the token in the Authorization header:
```
Authorization: Bearer <your-jwt-token>
```

## Endpoints

### Authentication

#### POST /auth/login
Login user and return JWT token.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "id": 1,
      "email": "user@example.com",
      "full_name": "John Doe",
      "role": "student"
    }
  },
  "status": 200
}
```

#### POST /auth/register
Register a new user.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "full_name": "John Doe",
  "role": "student"
}
```

#### GET /auth/me
Get current user profile (requires authentication).

**Headers:**
```
Authorization: Bearer <token>
```

### Users

#### GET /users/profile
Get user profile (requires authentication).

#### PUT /users/profile
Update user profile (requires authentication).

**Request Body:**
```json
{
  "full_name": "John Smith",
  "email": "johnsmith@example.com"
}
```

### Health

#### GET /health
Check API health status.

**Response:**
```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "service": "infotess-api-v2",
    "version": "2.0.0",
    "timestamp": "2024-01-01T12:00:00+00:00",
    "database": "connected",
    "environment": "production"
  },
  "status": 200
}
```

## Error Responses

All errors follow this format:
```json
{
  "success": false,
  "error": "Error message",
  "status": 400,
  "details": {
    "field": "Field-specific error"
  }
}
```

### Common Status Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `404` - Not Found
- `409` - Conflict
- `422` - Validation Error
- `429` - Too Many Requests
- `500` - Internal Server Error
- `503` - Service Unavailable

## Rate Limiting
- Standard endpoints: 60 requests per minute
- Sensitive endpoints (login): 10 requests per minute

Rate limit headers are included in responses:
- `X-RateLimit-Limit` - Request limit
- `X-RateLimit-Remaining` - Remaining requests
- `X-RateLimit-Reset` - Reset time

## Environment Variables

Required environment variables for Vercel deployment:
- `DB_HOST` - Database host
- `DB_NAME` - Database name
- `DB_USER` - Database username
- `DB_PASS` - Database password
- `JWT_SECRET` - JWT signing secret
- `APP_ENV` - Application environment

## Security Features
- JWT authentication with expiration
- CORS headers for cross-origin requests
- Input validation and sanitization
- SQL injection protection via prepared statements
- Rate limiting to prevent abuse
- Password hashing with PHP's password_hash()

## Migration from v1
- Update base URL from `/api/` to `/api/v2/`
- Implement JWT authentication in your frontend
- Update error handling to use new response format
- Update request headers to include CORS and JWT tokens
