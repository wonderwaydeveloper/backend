#!/bin/bash

# WonderWay Security Testing Script
# Usage: ./security_test.sh [base_url]

BASE_URL=${1:-"http://localhost:8000"}
API_URL="$BASE_URL/api"

echo "🔒 Starting WonderWay Security Tests..."
echo "Target: $API_URL"
echo "=================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counter
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Function to run test
run_test() {
    local test_name="$1"
    local command="$2"
    local expected_status="$3"
    
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    echo -n "Testing: $test_name... "
    
    response=$(eval "$command" 2>/dev/null)
    status=$(echo "$response" | tail -n1)
    
    if [[ "$status" == "$expected_status" ]]; then
        echo -e "${GREEN}PASS${NC}"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "${RED}FAIL${NC} (Expected: $expected_status, Got: $status)"
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
}

echo "🧪 WAF Tests"
echo "------------"

# SQL Injection Tests
run_test "SQL Injection - UNION SELECT" \
    "curl -s -o /dev/null -w '%{http_code}' -X POST '$API_URL/test' -H 'Content-Type: application/json' -d '{\"content\":\"test; UNION SELECT * FROM users\"}'" \
    "403"

run_test "SQL Injection - DROP TABLE" \
    "curl -s -o /dev/null -w '%{http_code}' -X POST '$API_URL/test' -H 'Content-Type: application/json' -d '{\"content\":\"test; DROP TABLE users; --\"}'" \
    "403"

run_test "SQL Injection - OR 1=1" \
    "curl -s -o /dev/null -w '%{http_code}' -X POST '$API_URL/test' -H 'Content-Type: application/json' -d '{\"content\":\"admin OR 1=1\"}'" \
    "403"

# XSS Tests
run_test "XSS - Script Tag" \
    "curl -s -o /dev/null -w '%{http_code}' -X POST '$API_URL/test' -H 'Content-Type: application/json' -d '{\"content\":\"<script>alert(1)</script>\"}'" \
    "403"

run_test "XSS - JavaScript URL" \
    "curl -s -o /dev/null -w '%{http_code}' -X POST '$API_URL/test' -H 'Content-Type: application/json' -d '{\"content\":\"javascript:alert(1)\"}'" \
    "403"

run_test "XSS - Event Handler" \
    "curl -s -o /dev/null -w '%{http_code}' -X POST '$API_URL/test' -H 'Content-Type: application/json' -d '{\"content\":\"<img src=x onerror=alert(1)>\"}'" \
    "403"

# File Inclusion Tests
run_test "LFI - etc/passwd" \
    "curl -s -o /dev/null -w '%{http_code}' -X POST '$API_URL/test' -H 'Content-Type: application/json' -d '{\"content\":\"../../../etc/passwd\"}'" \
    "403"

run_test "LFI - Windows System" \
    "curl -s -o /dev/null -w '%{http_code}' -X POST '$API_URL/test' -H 'Content-Type: application/json' -d '{\"content\":\"..\\\\..\\\\windows\\\\system32\"}'" \
    "403"

echo ""
echo "🚦 Rate Limiting Tests"
echo "---------------------"

# Rate Limiting Test
echo -n "Testing: Rate Limiting (65 requests)... "
rate_limit_failed=0
for i in {1..65}; do
    status=$(curl -s -o /dev/null -w '%{http_code}' "$API_URL/posts")
    if [[ $i -gt 60 && "$status" != "429" ]]; then
        rate_limit_failed=1
    fi
done

TOTAL_TESTS=$((TOTAL_TESTS + 1))
if [[ $rate_limit_failed -eq 0 ]]; then
    echo -e "${GREEN}PASS${NC}"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${RED}FAIL${NC}"
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi

# Burst Detection Test
echo -n "Testing: Burst Detection... "
burst_blocked=0
for i in {1..12}; do
    status=$(curl -s -o /dev/null -w '%{http_code}' "$API_URL/posts")
    if [[ $i -gt 10 && "$status" == "429" ]]; then
        burst_blocked=1
        break
    fi
done

TOTAL_TESTS=$((TOTAL_TESTS + 1))
if [[ $burst_blocked -eq 1 ]]; then
    echo -e "${GREEN}PASS${NC}"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${RED}FAIL${NC}"
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi

echo ""
echo "🔍 Header Security Tests"
echo "-----------------------"

# Security Headers Test
headers_response=$(curl -s -I "$API_URL/health")

check_header() {
    local header_name="$1"
    local expected="$2"
    
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    echo -n "Testing: $header_name... "
    
    if echo "$headers_response" | grep -qi "$header_name"; then
        echo -e "${GREEN}PASS${NC}"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "${RED}FAIL${NC}"
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
}

check_header "X-Frame-Options"
check_header "X-Content-Type-Options"
check_header "X-XSS-Protection"
check_header "Strict-Transport-Security"

echo ""
echo "🎭 Suspicious User-Agent Tests"
echo "-----------------------------"

# Suspicious User-Agent Tests
run_test "Suspicious UA - SQLMap" \
    "curl -s -o /dev/null -w '%{http_code}' -H 'User-Agent: sqlmap/1.0' '$API_URL/posts'" \
    "403"

run_test "Suspicious UA - Nikto" \
    "curl -s -o /dev/null -w '%{http_code}' -H 'User-Agent: Nikto/2.1.6' '$API_URL/posts'" \
    "403"

run_test "Suspicious UA - Burp Suite" \
    "curl -s -o /dev/null -w '%{http_code}' -H 'User-Agent: Burp Suite' '$API_URL/posts'" \
    "403"

echo ""
echo "📊 Test Results Summary"
echo "======================"
echo -e "Total Tests: $TOTAL_TESTS"
echo -e "${GREEN}Passed: $PASSED_TESTS${NC}"
echo -e "${RED}Failed: $FAILED_TESTS${NC}"

if [[ $FAILED_TESTS -eq 0 ]]; then
    echo -e "\n${GREEN}🎉 All security tests passed!${NC}"
    exit 0
else
    echo -e "\n${RED}⚠️  Some security tests failed!${NC}"
    exit 1
fi