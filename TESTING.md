# Backend Testing Guide - Local Setup with XAMPP

## Prerequisites
- ✅ XAMPP with Apache & MySQL running
- ✅ PHP 8.0+ available (`php -v`)
- ✅ curl or Postman for API testing

---

## Step 1: Database Setup

### Option A: Using phpMyAdmin (GUI)
1. Open `http://localhost/phpmyadmin`
2. Go to "Import" tab
3. Upload `/backend/database/schema.sql`
4. Click "Go"

### Option B: Using MySQL CLI
```bash
mysql -u root -p < /path/to/backend/database/schema.sql
```

### Option C: Using XAMPP Shell
```bash
cd /path/to/backend/database
mysql -u root < schema.sql
```

Verify the database was created:
```bash
mysql -u root -e "USE sms; SHOW TABLES;"
```

**Expected output:**
```
Tables_in_sms
attendance
classes
grades
students
subjects
teachers
users
```

---

## Step 2: Configure Apache for Backend

### Option A: Use PHP Built-in Server (Easiest)
```bash
cd /path/to/backend/public
php -S localhost:8000
```
Your API will be at: `http://localhost:8000/api`

### Option B: Use XAMPP Apache (If already set up)
Place the `backend` folder in XAMPP's `htdocs`:
```bash
cp -r /path/to/backend /Applications/XAMPP/htdocs/sms
# or your XAMPP path
```

Then access: `http://localhost/sms/public/`

---

## Step 3: Test the Backend Endpoints

### A. Test User Registration
```bash
curl -X POST http://localhost:8000/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "id": 3,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "student"
  },
  "errors": null
}
```

### B. Test User Login
```bash
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "test"
  }'
```

**Note:** The default test user from database:
- Email: `test@example.com`
- Password: `test` (bcrypt hash: `$2y$10$rOYqEwnbUMupLbWdDOmNk..DqRm0mYNGxdEOllTqdmJ/B9spbuQ8C`)

**Expected Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJhbGc...",
    "user": {
      "id": 1,
      "name": "Test User",
      "email": "test@example.com",
      "role": "admin"
    }
  }
}
```

**Save the token** for authenticated requests:
```bash
TOKEN="eyJhbGc..."
```

### C. Test Authenticated Endpoints (Using Token)

#### Get All Users (Admin Only)
```bash
curl -X GET http://localhost:8000/users \
  -H "Authorization: Bearer $TOKEN"
```

#### Get All Students
```bash
curl -X GET http://localhost:8000/students \
  -H "Authorization: Bearer $TOKEN"
```

#### Get All Teachers
```bash
curl -X GET http://localhost:8000/teachers \
  -H "Authorization: Bearer $TOKEN"
```

#### Get All Classes
```bash
curl -X GET http://localhost:8000/classes \
  -H "Authorization: Bearer $TOKEN"
```

### D. Test Validation Errors

Try creating a student with missing required fields:
```bash
curl -X POST http://localhost:8000/students \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "student_number": "STU001"
  }'
```

**Expected Response (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "data": null,
  "errors": {
    "user_id": ["The user_id field is required."],
    "class_id": ["The class_id field is required."]
  }
}
```

---

## Step 4: Using Postman for Testing

### Import Collection
1. Open **Postman**
2. Create a new **Collection** called "SMS API"
3. Add these requests:

#### Request 1: Register
- **Method**: POST
- **URL**: `http://localhost:8000/auth/register`
- **Body** (JSON):
```json
{
  "name": "Test User 2",
  "email": "testuser2@example.com",
  "password": "password123"
}
```

#### Request 2: Login
- **Method**: POST
- **URL**: `http://localhost:8000/auth/login`
- **Body** (JSON):
```json
{
  "email": "test@example.com",
  "password": "test"
}
```
- **Save token**: After response, go to **Tests** tab and add:
```javascript
var jsonData = pm.response.json();
pm.environment.set("token", jsonData.data.token);
```

#### Request 3: Get All Users
- **Method**: GET
- **URL**: `http://localhost:8000/users`
- **Headers**:
  - `Authorization`: `Bearer {{token}}`

#### Request 4: Create Class
- **Method**: POST
- **URL**: `http://localhost:8000/classes`
- **Headers**:
  - `Authorization`: `Bearer {{token}}`
  - `Content-Type`: `application/json`
- **Body** (JSON):
```json
{
  "name": "Class 1A",
  "section": "A",
  "year": 2026
}
```

---

## Step 5: JWT Token Validation

Test the new JWT implementation:

```bash
# Login
RESPONSE=$(curl -s -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"test"}')

TOKEN=$(echo $RESPONSE | jq -r '.data.token')

# Verify token is base64url encoded (no padding)
echo "Token: $TOKEN"

# Try expired token (wait 1 hour or modify JWT manually)
curl -X GET http://localhost:8000/students \
  -H "Authorization: Bearer invalid-token"
# Should return 401: "Unauthorized: Invalid token"
```

---

## Step 6: Error Handling Tests

### Test 1: Missing JWT Secret
Remove `JWT_SECRET` from `.env` and restart:
```bash
# Should throw RuntimeException and 500 error
curl -X GET http://localhost:8000/students \
  -H "Authorization: Bearer any-token"
```

### Test 2: Invalid Token Signature
```bash
FAKE_TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6MX0.invalid_signature"
curl -X GET http://localhost:8000/students \
  -H "Authorization: Bearer $FAKE_TOKEN"
# Should return 401: "Unauthorized: Invalid token"
```

### Test 3: Expired Token
Modify a token's `exp` claim to a past timestamp and test:
```bash
curl -X GET http://localhost:8000/students \
  -H "Authorization: Bearer expired-token"
# Should return 401: "Unauthorized: Invalid token"
```

### Test 4: Rate Limiting
Try 6 login attempts in 5 minutes:
```bash
for i in {1..6}; do
  curl -X POST http://localhost:8000/auth/login \
    -H "Content-Type: application/json" \
    -d '{"email":"test@example.com","password":"wrong"}'
  echo "\nAttempt $i"
done
# 6th attempt should return 429: "Too many login attempts"
```

---

## Step 7: Quick Testing Checklist

- [ ] Database imported successfully with 8 tables
- [ ] Default user can login: `test@example.com` / `test`
- [ ] JWT token is returned with `iat` and `exp` claims
- [ ] Can register new user
- [ ] Auth token required for non-auth endpoints (returns 401 without token)
- [ ] Validation errors return `errors` field with details
- [ ] Rate limiting works (429 after 5 failed logins)
- [ ] CORS headers present in responses
- [ ] Student creation requires `user_id`, `student_number`, `class_id`
- [ ] Attendance returns single record (not all student records)

---

## Debugging Tips

### Enable PHP Error Logging
Edit `.env` or `php.ini`:
```ini
error_reporting = E_ALL
display_errors = 1
log_errors = 1
error_log = /tmp/php_errors.log
```

Check logs:
```bash
tail -f /tmp/php_errors.log
```

### Test Database Connection
Create `backend/test-db.php`:
```php
<?php
require_once 'config/database.php';
try {
    $db = Database::getInstance();
    $conn = $db->connect();
    echo "✓ Database connected!\n";
    echo "Database: " . getenv('DB_NAME') . "\n";
} catch (Exception $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "\n";
}
?>
```

Run:
```bash
php backend/test-db.php
```

### Check JWT Implementation
Create `backend/test-jwt.php`:
```php
<?php
require_once 'helpers/JWT.php';
require_once 'public/index.php'; // Load env

try {
    $token = JWT::encode(['id' => 1, 'email' => 'test@example.com'], 3600);
    echo "✓ Token created: " . substr($token, 0, 50) . "...\n";
    
    $decoded = JWT::decode($token);
    echo "✓ Token decoded: " . json_encode($decoded) . "\n";
} catch (Exception $e) {
    echo "✗ JWT error: " . $e->getMessage() . "\n";
}
?>
```

Run:
```bash
php backend/test-jwt.php
```

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| **502 Bad Gateway** | Check PHP error logs, restart Apache |
| **404 Auth endpoint not found** | Verify URL format: `/auth/login` not `/auth/login/` |
| **500 JWT_SECRET error** | Add `JWT_SECRET=your-secret-key` to `.env` |
| **Database connection failed** | Verify MySQL is running, check DB credentials in `.env` |
| **CORS error in browser** | Frontend URL in `.env` must match exactly |
| **Cannot create student** | Class must exist first, user must be registered |

---

## Next Steps
1. ✅ Test all endpoints above
2. Once all tests pass, move to **Frontend Testing**
3. Connect frontend to backend (update `NEXT_PUBLIC_API_URL`)
4. Test full end-to-end flow
