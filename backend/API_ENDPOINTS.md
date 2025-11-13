# API Endpoints Documentation

Base URL: `http://127.0.0.1:8000`

## 🔓 Public Endpoints (No Authentication Required)

### 1. Register User
- **Method:** `POST`
- **URL:** `/api/register`
- **Headers:** `Content-Type: application/json`
- **Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "role": "user"  // Optional: "admin", "user", or "moderator" (default: "user")
}
```
- **Response:** Returns user object and authentication token

---

### 2. Login
- **Method:** `POST`
- **URL:** `/api/login`
- **Headers:** `Content-Type: application/json`
- **Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```
- **Response:** Returns user object and authentication token

---

### 3. Test Endpoint
- **Method:** `GET`
- **URL:** `/api/test`
- **Response:** `"Welcome to test"`

---

### 4. Webhook - ChargilyPay
- **Method:** `POST`
- **URL:** `/api/chargilypay/webhook`
- **Description:** Public webhook endpoint for ChargilyPay payment notifications

---

### 5. Webhook Options (CORS)
- **Method:** `OPTIONS`
- **URL:** `/api/chargilypay/webhook`
- **Description:** CORS preflight request handler

---

### 6. Test Webhook
- **Method:** `POST`
- **URL:** `/api/chargily/webhook/test`
- **Description:** Test endpoint for webhook functionality

---

### 7. Simulate Webhook
- **Method:** `POST`
- **URL:** `/api/chargilypay/simulate-webhook`
- **Description:** Simulate a webhook call for testing

---

### 8. Health Check
- **Method:** `GET`
- **URL:** `/up`
- **Description:** Application health check endpoint

---

### 9. Root/Welcome Page
- **Method:** `GET`
- **URL:** `/`
- **Description:** Laravel welcome page

---

## 🔒 Protected Endpoints (Authentication Required)

**Note:** All protected endpoints require the `Authorization` header:
```
Authorization: Bearer {your_token_here}
```

### 10. Get Current User
- **Method:** `GET`
- **URL:** `/api/user`
- **Headers:** 
  - `Authorization: Bearer {token}`
  - `Accept: application/json`
- **Response:** Returns current authenticated user information

---

### 11. Logout
- **Method:** `POST`
- **URL:** `/api/logout`
- **Headers:** 
  - `Authorization: Bearer {token}`
  - `Content-Type: application/json`
- **Response:** Success message

---

### 12. Get History
- **Method:** `GET`
- **URL:** `/api/getHistory`
- **Headers:** 
  - `Authorization: Bearer {token}`
  - `Accept: application/json`
- **Response:** Returns user's history/results

---

### 13. Process PDF / Get Data From Agent
- **Method:** `POST`
- **URL:** `/api/getDataFromAgent`
- **Headers:** 
  - `Authorization: Bearer {token}`
  - `Content-Type: multipart/form-data` or `application/json`
- **Body:** (Depends on implementation - likely PDF file or data)
- **Note:** Has rate limiting middleware

---

### 14. ChargilyPay Redirect
- **Method:** `POST`
- **URL:** `/api/chargilypay/redirect`
- **Headers:** 
  - `Authorization: Bearer {token}`
  - `Content-Type: application/json`
- **Description:** Initiate payment redirect

---

### 15. ChargilyPay Back (Callback)
- **Method:** `GET`
- **URL:** `/api/chargilypay/back`
- **Headers:** 
  - `Authorization: Bearer {token}`
  - `Accept: application/json`
- **Description:** Payment callback handler

---

### 16. Get Statistics
- **Method:** `GET`
- **URL:** `/api/stats`
- **Headers:** 
  - `Authorization: Bearer {token}`
  - `Accept: application/json`
- **Response:** Returns statistics data

---

### 17. Dashboard (Paid Access Required)
- **Method:** `GET`
- **URL:** `/api/dashboard`
- **Headers:** 
  - `Authorization: Bearer {token}`
  - `Accept: application/json`
- **Middleware:** Requires `check.access` middleware (paid access)
- **Response:** `"Welcome to your paid dashboard!"`

---

## 👥 User Management Endpoints (Resource Routes)

All user management endpoints require authentication.

### 18. List Users
- **Method:** `GET`
- **URL:** `/api/users`
- **Headers:** 
  - `Authorization: Bearer {token}`
  - `Accept: application/json`
- **Query Parameters:**
  - `per_page` (optional): Number of items per page (default: 15)
  - `sort_by` (optional): Sort column - "name", "created_at", "updated_at" (default: "created_at")
  - `sort_direction` (optional): "asc" or "desc" (default: "asc")
  - `role` (optional): Filter by role
  - `start_date` (optional): Filter by start date
  - `end_date` (optional): Filter by end date (default: today)
  - `is_active` (optional): Filter by active status - "0" or "1"
- **Example:** `/api/users?per_page=10&sort_by=name&sort_direction=desc&role=user&is_active=1`

---

### 19. Create User
- **Method:** `POST`
- **URL:** `/api/users`
- **Headers:** 
  - `Authorization: Bearer {token}`
  - `Content-Type: application/json`
- **Body:** (Check UserController implementation)

---

### 20. Show User
- **Method:** `GET`
- **URL:** `/api/users/{user}`
- **Headers:** 
  - `Authorization: Bearer {token}`
  - `Accept: application/json`
- **Example:** `/api/users/1`

---

### 21. Update User
- **Method:** `PUT` or `PATCH`
- **URL:** `/api/users/{user}`
- **Headers:** 
  - `Authorization: Bearer {token}`
  - `Content-Type: application/json`
- **Example:** `/api/users/1`

---

### 22. Delete User
- **Method:** `DELETE`
- **URL:** `/api/users/{user}`
- **Headers:** 
  - `Authorization: Bearer {token}`
- **Example:** `/api/users/1`

---

### 23. Create User Form (Usually not used in API)
- **Method:** `GET`
- **URL:** `/api/users/create`
- **Headers:** 
  - `Authorization: Bearer {token}`

---

### 24. Edit User Form (Usually not used in API)
- **Method:** `GET`
- **URL:** `/api/users/{user}/edit`
- **Headers:** 
  - `Authorization: Bearer {token}`

---

## 🔐 Sanctum CSRF Cookie

### 25. Get CSRF Cookie
- **Method:** `GET`
- **URL:** `/sanctum/csrf-cookie`
- **Description:** Get CSRF cookie for SPA authentication

---

## 📁 Storage Files

### 26. Storage Files
- **Method:** `GET`
- **URL:** `/storage/{path}`
- **Description:** Access stored files (images, documents, etc.)
- **Example:** `/storage/users/avatar.jpg`

---

## 🧪 Testing Workflow

### Step 1: Register a new user
```bash
POST http://127.0.0.1:8000/api/register
Content-Type: application/json

{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "role": "user"
}
```

### Step 2: Login to get token
```bash
POST http://127.0.0.1:8000/api/login
Content-Type: application/json

{
    "email": "test@example.com",
    "password": "password123"
}
```

### Step 3: Use the token for protected endpoints
```bash
GET http://127.0.0.1:8000/api/user
Authorization: Bearer {token_from_step_2}
Accept: application/json
```

### Step 4: Test other endpoints with the token
- Get users list: `GET /api/users?per_page=10`
- Get history: `GET /api/getHistory`
- Get stats: `GET /api/stats`

---

## 📝 Notes

1. **Authentication:** Most endpoints require a Bearer token obtained from `/api/login` or `/api/register`
2. **Content-Type:** Use `application/json` for JSON requests
3. **Rate Limiting:** `/api/getDataFromAgent` has rate limiting middleware
4. **Access Control:** `/api/dashboard` requires paid access (check.access middleware)
5. **CORS:** API routes support CORS (configured in `config/cors.php`)

---

## 🛠️ Testing Tools

You can test these endpoints using:
- **Postman**
- **cURL**
- **Thunder Client** (VS Code extension)
- **Insomnia**
- **HTTPie**
- **Browser** (for GET requests only)

---

## Example cURL Commands

### Register
```bash
curl -X POST http://127.0.0.1:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"John Doe","email":"john@example.com","password":"password123"}'
```

### Login
```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"password123"}'
```

### Get Current User
```bash
curl -X GET http://127.0.0.1:8000/api/user \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Get Users List
```bash
curl -X GET "http://127.0.0.1:8000/api/users?per_page=10&sort_by=name" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

