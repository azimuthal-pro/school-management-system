#!/bin/bash

# Comprehensive Backend Testing Suite
# Tests: CRUD, Validation, Error Handling, Rate Limiting

API_URL="${1:-http://localhost:8000}"
RESULTS_FILE="/tmp/sms_comprehensive_tests.log"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Test counters
PASS=0
FAIL=0

# Helper function to make requests and check responses
test_request() {
    local name="$1"
    local method="$2"
    local endpoint="$3"
    local data="$4"
    local token="$5"
    local expected_code="$6"
    
    echo -n "  [$name] ... "
    
    if [ -z "$data" ]; then
        response=$(curl -s -w "\n%{http_code}" -X "$method" "$API_URL$endpoint" \
            -H "Content-Type: application/json" \
            ${token:+-H "Authorization: Bearer $token"})
    else
        response=$(curl -s -w "\n%{http_code}" -X "$method" "$API_URL$endpoint" \
            -H "Content-Type: application/json" \
            ${token:+-H "Authorization: Bearer $token"} \
            -d "$data")
    fi
    
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')
    
    if [ "$http_code" = "$expected_code" ]; then
        echo -e "${GREEN}✓ PASS${NC} (HTTP $http_code)"
        PASS=$((PASS + 1))
        echo "$body"
    else
        echo -e "${RED}✗ FAIL${NC} (Expected $expected_code, got $http_code)"
        FAIL=$((FAIL + 1))
        echo "$body"
    fi
}

echo -e "${BLUE}======================================${NC}"
echo -e "${BLUE}  SMS Backend Comprehensive Tests${NC}"
echo -e "${BLUE}======================================${NC}"
echo "API URL: $API_URL"
echo "Timestamp: $(date)"
echo ""

# ============================================================================
# 1. AUTHENTICATION & TOKEN SETUP
# ============================================================================
echo -e "${YELLOW}[1] Authentication Setup${NC}"

# Get token for subsequent tests
login_response=$(curl -s -X POST "$API_URL/auth/login" \
    -H "Content-Type: application/json" \
    -d '{"email":"test@example.com","password":"test"}')

TOKEN=$(echo "$login_response" | jq -r '.data.token' 2>/dev/null)

if [ -z "$TOKEN" ] || [ "$TOKEN" = "null" ]; then
    echo -e "${RED}✗ Could not obtain token${NC}"
    echo "Response: $login_response"
    exit 1
fi

echo -e "  ✓ Token obtained: ${TOKEN:0:50}..."
echo ""

# ============================================================================
# 2. CRUD OPERATIONS
# ============================================================================
echo -e "${YELLOW}[2] CRUD Operations Tests${NC}"

# 2.1 CREATE CLASS (prerequisite for students/teachers)
echo ""
echo "  2.1 Create Operations:"
class_response=$(test_request "POST /classes" "POST" "/classes" \
    '{"name":"Class A","section":"A","year":2026}' "$TOKEN" "201")
CLASS_ID=$(echo "$class_response" | jq -r '.data.id' 2>/dev/null)
echo ""

# 2.2 CREATE STUDENT
student_response=$(test_request "POST /students" "POST" "/students" \
    "{\"user_id\":2,\"student_number\":\"STU001\",\"class_id\":$CLASS_ID}" "$TOKEN" "201")
STUDENT_ID=$(echo "$student_response" | jq -r '.data.id' 2>/dev/null)
echo ""

# 2.3 CREATE TEACHER
teacher_response=$(test_request "POST /teachers" "POST" "/teachers" \
    "{\"user_id\":2,\"specialization\":\"Mathematics\"}" "$TOKEN" "201")
TEACHER_ID=$(echo "$teacher_response" | jq -r '.data.id' 2>/dev/null)
echo ""

# 2.4 CREATE SUBJECT
subject_response=$(test_request "POST /subjects" "POST" "/subjects" \
    '{"name":"Mathematics","code":"MATH"}' "$TOKEN" "201")
SUBJECT_ID=$(echo "$subject_response" | jq -r '.data.id' 2>/dev/null)
echo ""

echo "  2.2 Read Operations:"
test_request "GET /classes" "GET" "/classes" "" "$TOKEN" "200" > /dev/null
test_request "GET /classes/:id" "GET" "/classes/$CLASS_ID" "" "$TOKEN" "200" > /dev/null
echo ""
test_request "GET /students" "GET" "/students" "" "$TOKEN" "200" > /dev/null
if [ ! -z "$STUDENT_ID" ] && [ "$STUDENT_ID" != "null" ]; then
    test_request "GET /students/:id" "GET" "/students/$STUDENT_ID" "" "$TOKEN" "200" > /dev/null
fi
echo ""
test_request "GET /teachers" "GET" "/teachers" "" "$TOKEN" "200" > /dev/null
if [ ! -z "$TEACHER_ID" ] && [ "$TEACHER_ID" != "null" ]; then
    test_request "GET /teachers/:id" "GET" "/teachers/$TEACHER_ID" "" "$TOKEN" "200" > /dev/null
fi
echo ""

echo "  2.3 Update Operations:"
if [ ! -z "$CLASS_ID" ] && [ "$CLASS_ID" != "null" ]; then
    test_request "PUT /classes/:id" "PUT" "/classes/$CLASS_ID" \
        '{"name":"Class A Updated","section":"A","year":2026}' "$TOKEN" "200" > /dev/null
fi
echo ""

if [ ! -z "$STUDENT_ID" ] && [ "$STUDENT_ID" != "null" ]; then
    test_request "PUT /students/:id" "PUT" "/students/$STUDENT_ID" \
        "{\"user_id\":2,\"student_number\":\"STU001_UPDATED\",\"class_id\":$CLASS_ID}" "$TOKEN" "200" > /dev/null
fi
echo ""

echo "  2.4 Delete Operations:"
# Test delete (don't actually delete - we might need them for other tests)
# Just verify the endpoint exists with correct auth
test_request "DELETE /classes/:id (auth check)" "DELETE" "/classes/999999" "" "$TOKEN" "404" > /dev/null
echo ""

# ============================================================================
# 3. VALIDATION ERRORS (422)
# ============================================================================
echo -e "${YELLOW}[3] Validation Error Tests (422)${NC}"

echo ""
echo "  3.1 Missing Required Fields:"
test_request "POST /students - missing user_id" "POST" "/students" \
    '{"student_number":"STU002"}' "$TOKEN" "422" > /dev/null
test_request "POST /students - missing student_number" "POST" "/students" \
    "{\"user_id\":2,\"class_id\":$CLASS_ID}" "$TOKEN" "422" > /dev/null
test_request "POST /students - missing class_id" "POST" "/students" \
    '{"user_id":2,"student_number":"STU003"}' "$TOKEN" "422" > /dev/null
echo ""

echo "  3.2 Invalid Input Types:"
test_request "POST /classes - invalid year" "POST" "/classes" \
    '{"name":"Test","section":"A","year":"not-a-year"}' "$TOKEN" "422" > /dev/null
test_request "POST /students - invalid user_id" "POST" "/students" \
    '{"user_id":"not-a-number","student_number":"STU004","class_id":1}' "$TOKEN" "422" > /dev/null
echo ""

echo "  3.3 Register Validation:"
test_request "Register - invalid email" "POST" "/auth/register" \
    '{"name":"Test","email":"not-an-email","password":"pass123"}' "" "422" > /dev/null
test_request "Register - password too short" "POST" "/auth/register" \
    '{"name":"Test","email":"new@example.com","password":"123"}' "" "422" > /dev/null
test_request "Register - missing name" "POST" "/auth/register" \
    '{"email":"another@example.com","password":"password123"}' "" "422" > /dev/null
echo ""

# ============================================================================
# 4. ERROR HANDLING
# ============================================================================
echo -e "${YELLOW}[4] Error Handling Tests${NC}"

echo ""
echo "  4.1 Authentication Errors (401):"
test_request "GET /students - no token" "GET" "/students" "" "" "401" > /dev/null
test_request "GET /students - invalid token" "GET" "/students" "" "invalid-token-12345" "401" > /dev/null
test_request "GET /students - malformed token" "GET" "/students" "" "not.a.token" "401" > /dev/null
echo ""

echo "  4.2 Not Found Errors (404):"
test_request "GET /students/999999 - invalid ID" "GET" "/students/999999" "" "$TOKEN" "404" > /dev/null
test_request "GET /teachers/999999 - invalid ID" "GET" "/teachers/999999" "" "$TOKEN" "404" > /dev/null
test_request "GET /classes/999999 - invalid ID" "GET" "/classes/999999" "" "$TOKEN" "404" > /dev/null
echo ""

echo "  4.3 Invalid Endpoints:"
test_request "GET /invalid-endpoint" "GET" "/invalid-endpoint" "" "$TOKEN" "404" > /dev/null
echo ""

# ============================================================================
# 5. RATE LIMITING
# ============================================================================
echo -e "${YELLOW}[5] Rate Limiting Tests${NC}"

echo ""
echo "  5.1 Login Rate Limiting (5 attempts per 5 minutes):"
echo "  Attempting 6 failed logins..."

for i in {1..6}; do
    response=$(curl -s -w "\n%{http_code}" -X POST "$API_URL/auth/login" \
        -H "Content-Type: application/json" \
        -d '{"email":"test@example.com","password":"wrongpassword"}')
    
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')
    
    if [ $i -le 5 ]; then
        # First 5 should be 401 (invalid credentials)
        if [ "$http_code" = "401" ]; then
            echo -n "  Attempt $i: ${GREEN}✓${NC} (401 - Invalid credentials)"
            PASS=$((PASS + 1))
        else
            echo -n "  Attempt $i: ${RED}✗${NC} (Expected 401, got $http_code)"
            FAIL=$((FAIL + 1))
        fi
    else
        # 6th should be 429 (rate limited)
        if [ "$http_code" = "429" ]; then
            echo -n "  Attempt $i: ${GREEN}✓${NC} (429 - Rate limited)"
            PASS=$((PASS + 1))
        else
            echo -n "  Attempt $i: ${RED}✗${NC} (Expected 429, got $http_code)"
            FAIL=$((FAIL + 1))
        fi
    fi
    
    # Brief pause between attempts
    sleep 0.1
    echo ""
done

echo ""

# ============================================================================
# SUMMARY
# ============================================================================
TOTAL=$((PASS + FAIL))
SUCCESS_RATE=$(echo "scale=1; ($PASS / $TOTAL) * 100" | bc)

echo -e "${BLUE}======================================${NC}"
echo -e "${BLUE}  Test Summary${NC}"
echo -e "${BLUE}======================================${NC}"
echo "Total Tests: $TOTAL"
echo -e "Passed: ${GREEN}$PASS${NC}"
echo -e "Failed: ${RED}$FAIL${NC}"
echo "Success Rate: $SUCCESS_RATE%"
echo ""

if [ $FAIL -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed!${NC}"
    exit 0
else
    echo -e "${RED}✗ Some tests failed${NC}"
    exit 1
fi
