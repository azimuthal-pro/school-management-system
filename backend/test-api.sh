#!/bin/bash

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
API_URL="${1:-http://localhost:8000}"
RESULTS_FILE="/tmp/sms_test_results.txt"

echo -e "${BLUE}===== SMS Backend Testing Suite =====${NC}"
echo "API URL: $API_URL"
echo "Testing timestamp: $(date)"
echo ""

# Clear results file
> "$RESULTS_FILE"

# Test counters
TOTAL=0
PASSED=0
FAILED=0

# Function to test endpoint
test_endpoint() {
    local name="$1"
    local method="$2"
    local endpoint="$3"
    local data="$4"
    local token="$5"
    local expected_status="$6"
    
    TOTAL=$((TOTAL + 1))
    
    echo -n "Testing: $name ... "
    
    if [ -z "$data" ]; then
        # GET request
        response=$(curl -s -w "\n%{http_code}" -X "$method" "$API_URL$endpoint" \
            -H "Content-Type: application/json" \
            ${token:+-H "Authorization: Bearer $token"})
    else
        # POST/PUT request
        response=$(curl -s -w "\n%{http_code}" -X "$method" "$API_URL$endpoint" \
            -H "Content-Type: application/json" \
            ${token:+-H "Authorization: Bearer $token"} \
            -d "$data")
    fi
    
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')
    
    if [ "$http_code" = "$expected_status" ]; then
        echo -e "${GREEN}✓ PASS${NC} (HTTP $http_code)"
        PASSED=$((PASSED + 1))
        echo "$name: PASS" >> "$RESULTS_FILE"
    else
        echo -e "${RED}✗ FAIL${NC} (Expected $expected_status, got $http_code)"
        FAILED=$((FAILED + 1))
        echo "$name: FAIL (Expected $expected_status, got $http_code)" >> "$RESULTS_FILE"
        echo "Response: $body" >> "$RESULTS_FILE"
    fi
    
    # Return the body for token extraction
    echo "$body"
}

# Test 1: Database connectivity
echo -e "\n${YELLOW}[1] Database & Auth Tests${NC}"

# Register new user
echo -n "Testing: User Registration ... "
register_response=$(test_endpoint "User Registration" "POST" "/auth/register" \
    '{"name":"Test User '$(date +%s)'","email":"test'$(date +%s)'@example.com","password":"password123"}' "" "201")

# Login with existing user
echo -n "Testing: User Login ... "
login_response=$(test_endpoint "User Login" "POST" "/auth/login" \
    '{"email":"test@example.com","password":"test"}' "" "200")

# Extract token from login response
TOKEN=$(echo "$login_response" | jq -r '.data.token' 2>/dev/null)

if [ -z "$TOKEN" ] || [ "$TOKEN" = "null" ]; then
    echo -e "${RED}✗ Could not extract token from login response${NC}"
    echo "Login response: $login_response"
    exit 1
fi

echo -e "${GREEN}✓ Token extracted: ${TOKEN:0:50}...${NC}\n"

# Test 2: Authenticated endpoints
echo -e "${YELLOW}[2] User Management Tests${NC}"
test_endpoint "Get All Users" "GET" "/users" "" "$TOKEN" "200" > /dev/null
test_endpoint "Get User by ID" "GET" "/users/1" "" "$TOKEN" "200" > /dev/null

# Test 3: Student endpoints
echo -e "\n${YELLOW}[3] Student Management Tests${NC}"
test_endpoint "Get All Students" "GET" "/students" "" "$TOKEN" "200" > /dev/null
test_endpoint "Get Student by ID" "GET" "/students/1" "" "$TOKEN" "200" > /dev/null

# Test 4: Teacher endpoints
echo -e "\n${YELLOW}[4] Teacher Management Tests${NC}"
test_endpoint "Get All Teachers" "GET" "/teachers" "" "$TOKEN" "200" > /dev/null

# Test 5: Class endpoints
echo -e "\n${YELLOW}[5] Class Management Tests${NC}"
test_endpoint "Get All Classes" "GET" "/classes" "" "$TOKEN" "200" > /dev/null

# Test 6: Error handling
echo -e "\n${YELLOW}[6] Error Handling Tests${NC}"
test_endpoint "Invalid Token (401)" "GET" "/students" "" "invalid-token" "401" > /dev/null
test_endpoint "Missing Token (401)" "GET" "/students" "" "" "401" > /dev/null
test_endpoint "Validation Error (422)" "POST" "/students" '{"student_number":"STU001"}' "$TOKEN" "422" > /dev/null
test_endpoint "Not Found (404)" "GET" "/students/999999" "" "$TOKEN" "404" > /dev/null

# Test 7: CORS
echo -e "\n${YELLOW}[7] CORS & Headers Tests${NC}"
cors_response=$(curl -s -i -X OPTIONS "$API_URL/students" \
    -H "Origin: http://localhost:3000" 2>&1)

if echo "$cors_response" | grep -q "Access-Control-Allow-Origin"; then
    echo -e "${GREEN}✓ PASS${NC} - CORS headers present"
    PASSED=$((PASSED + 1))
else
    echo -e "${RED}✗ FAIL${NC} - CORS headers missing"
    FAILED=$((FAILED + 1))
fi

# Summary
echo -e "\n${BLUE}===== Test Summary =====${NC}"
echo "Total Tests: $TOTAL"
echo -e "Passed: ${GREEN}$PASSED${NC}"
echo -e "Failed: ${RED}$FAILED${NC}"
echo "Success Rate: $(echo "scale=2; ($PASSED / $TOTAL) * 100" | bc)%"
echo ""
echo "Detailed results saved to: $RESULTS_FILE"

# Exit with appropriate code
if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed!${NC}"
    exit 0
else
    echo -e "${RED}✗ Some tests failed. Check $RESULTS_FILE for details.${NC}"
    exit 1
fi
