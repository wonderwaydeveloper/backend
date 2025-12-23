#!/bin/bash

echo "🚀 WonderWay Load Testing Started..."

# Test Configuration
BASE_URL="http://localhost:8000"
CONCURRENT_USERS=50
TOTAL_REQUESTS=1000

echo "📊 Testing Configuration:"
echo "  - Base URL: $BASE_URL"
echo "  - Concurrent Users: $CONCURRENT_USERS"
echo "  - Total Requests: $TOTAL_REQUESTS"
echo ""

# Test 1: Public Posts API
echo "🔍 Test 1: Public Posts API"
ab -n $TOTAL_REQUESTS -c $CONCURRENT_USERS -H "Accept: application/json" $BASE_URL/api/posts > results_posts.txt
echo "  ✓ Results saved to results_posts.txt"

# Test 2: Health Check
echo "🔍 Test 2: Health Check Endpoint"
ab -n 500 -c 25 $BASE_URL/api/health > results_health.txt
echo "  ✓ Results saved to results_health.txt"

# Test 3: Search API (if available)
echo "🔍 Test 3: Search Performance"
ab -n 200 -c 10 "$BASE_URL/api/search/posts?q=test" > results_search.txt 2>/dev/null || echo "  ⚠️ Search endpoint not accessible"

echo ""
echo "📈 Performance Summary:"
echo "Posts API:"
grep "Requests per second" results_posts.txt || echo "  - No results found"
grep "Time per request" results_posts.txt | head -1 || echo "  - No timing data"

echo ""
echo "Health Check:"
grep "Requests per second" results_health.txt || echo "  - No results found"
grep "Time per request" results_health.txt | head -1 || echo "  - No timing data"

echo ""
echo "✅ Load testing completed!"
echo "📁 Check results_*.txt files for detailed analysis"