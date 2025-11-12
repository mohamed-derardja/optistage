# API Endpoint Testing Guide

This guide provides examples of how to test all available API endpoints.

## Base URL
```
http://127.0.0.1:8000/api
```

---

## Public Endpoints (No Authentication Required)

### 1. GET /api/test
**Description:** Simple test endpoint to verify GET requests work.

**Request:**
```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/test" -Method GET
```

**cURL:**
```bash
curl http://127.0.0.1:8000/api/test
```

**Expected Response:**
```json
{
  "success": true,
  "message": "GET request works!",
  "method": "GET"
}
```

---

### 2. POST /api/test
**Description:** Simple test endpoint to verify POST requests work.

**Request:**
```powershell
$body = @{test = "data"} | ConvertTo-Json
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/test" -Method POST -ContentType "application/json" -Body $body
```

**cURL:**
```bash
curl -X POST http://127.0.0.1:8000/api/test \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "POST request works!",
  "method": "POST",
  "received_data": {
    "test": "data"
  }
}
```

---

### 3. POST /api/register
**Description:** Register a new user account.

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john.doe@example.com",
  "password": "password123",
  "role": "user"  // Optional: "admin", "user", or "moderator"
}
```

**PowerShell:**
```powershell
$body = @{
    name = "John Doe"
    email = "john.doe@example.com"
    password = "password123"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/register" `
    -Method POST `
    -ContentType "application/json" `
    -Body $body
```

**cURL:**
```bash
curl -X POST http://127.0.0.1:8000/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john.doe@example.com",
    "password": "password123"
  }'
```

**Expected Response (201):**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john.doe@example.com",
    "role": "user",
    "created_at": "2025-11-12T21:00:00.000000Z",
    "updated_at": "2025-11-12T21:00:00.000000Z"
  },
  "token": "1|abc123...",
  "user_role": "user"
}
```

**Validation Errors (422):**
- Missing required fields
- Email already exists
- Password too short (minimum 6 characters)
- Invalid email format

---

### 4. POST /api/login
**Description:** Login with email and password to get an authentication token.

**Request Body:**
```json
{
  "email": "john.doe@example.com",
  "password": "password123"
}
```

**PowerShell:**
```powershell
$body = @{
    email = "john.doe@example.com"
    password = "password123"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/login" `
    -Method POST `
    -ContentType "application/json" `
    -Body $body
```

**cURL:**
```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "john.doe@example.com",
    "password": "password123"
  }'
```

**Expected Response (200):**
```json
{
  "success": true,
  "message": "User logged in successfully.",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john.doe@example.com",
    "role": "user"
  },
  "token": "2|xyz789..."
}
```

**Error Responses:**
- 401: Invalid credentials
- 422: Validation errors (missing email/password, invalid format)

---

## Protected Endpoints (Authentication Required)

All protected endpoints require a Bearer token in the Authorization header.

**Header Format:**
```
Authorization: Bearer {your_token_here}
```

---

### 5. GET /api/user
**Description:** Get the currently authenticated user's information.

**PowerShell:**
```powershell
$token = "your_token_here"
$headers = @{
    "Authorization" = "Bearer $token"
    "Accept" = "application/json"
}

Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/user" `
    -Method GET `
    -Headers $headers
```

**cURL:**
```bash
curl http://127.0.0.1:8000/api/user \
  -H "Authorization: Bearer your_token_here" \
  -H "Accept: application/json"
```

**Expected Response (200):**
```json
{
  "success": true,
  "message": "User retrieved successfully.",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john.doe@example.com",
    "role": "user",
    "access_expires_at": null
  }
}
```

---

### 6. POST /api/logout
**Description:** Logout and invalidate the current authentication token.

**PowerShell:**
```powershell
$token = "your_token_here"
$headers = @{
    "Authorization" = "Bearer $token"
    "Accept" = "application/json"
}

Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/logout" `
    -Method POST `
    -Headers $headers
```

**cURL:**
```bash
curl -X POST http://127.0.0.1:8000/api/logout \
  -H "Authorization: Bearer your_token_here" \
  -H "Accept: application/json"
```

**Expected Response (200):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

## Quick Test Script

Run the automated test script:

```powershell
cd backend
.\test-endpoints.ps1
```

This will test all endpoints automatically.

---

## Common HTTP Status Codes

- **200 OK**: Request successful
- **201 Created**: Resource created successfully (registration)
- **401 Unauthorized**: Authentication required or invalid credentials
- **403 Forbidden**: Access denied (insufficient permissions)
- **404 Not Found**: Endpoint doesn't exist
- **405 Method Not Allowed**: Wrong HTTP method (e.g., GET instead of POST)
- **422 Unprocessable Entity**: Validation errors
- **500 Internal Server Error**: Server error

---

## Testing Tips

1. **Use Postman or Thunder Client** for easier testing
2. **Save your token** after login to test protected endpoints
3. **Use unique emails** when testing registration (add timestamp)
4. **Check response headers** for additional information
5. **Read error messages** - they tell you exactly what's wrong

---

## Example: Complete Registration Flow

```powershell
# 1. Register a new user
$body = @{
    name = "Test User"
    email = "test$(Get-Date -Format 'yyyyMMddHHmmss')@example.com"
    password = "password123"
} | ConvertTo-Json

$registerResponse = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/register" `
    -Method POST `
    -ContentType "application/json" `
    -Body $body

$token = $registerResponse.token
Write-Host "Registered! Token: $token"

# 2. Get current user info
$headers = @{
    "Authorization" = "Bearer $token"
    "Accept" = "application/json"
}

$userResponse = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/user" `
    -Method GET `
    -Headers $headers

Write-Host "Current user: $($userResponse.user.email)"

# 3. Logout
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/logout" `
    -Method POST `
    -Headers $headers

Write-Host "Logged out successfully!"
```

