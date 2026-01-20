#!/bin/bash

# ============================================
# Test Script untuk API Riwayat Peminjaman
# Gunakan: bash test-borrowing-api.sh
# ============================================

BASE_URL="http://localhost/perpustakaan-online/public/api"
API_ENDPOINT="borrowing-history.php"
COLOR_GREEN='\033[0;32m'
COLOR_RED='\033[0;31m'
COLOR_BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${COLOR_BLUE}=== Testing Borrowing History API ===${NC}\n"

# Test 1: API tanpa login (harus error 401)
echo -e "${COLOR_BLUE}Test 1: Access without session (expected 401)${NC}"
RESPONSE=$(curl -s -w "\n%{http_code}" "$BASE_URL/$API_ENDPOINT")
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

if [ "$HTTP_CODE" = "401" ]; then
    echo -e "${COLOR_GREEN}✓ PASS${NC} - Got 401 Unauthorized"
else
    echo -e "${COLOR_RED}✗ FAIL${NC} - Expected 401, got $HTTP_CODE"
fi
echo -e "Response: $BODY\n"

# Test 2: API dengan session cookie (simulasi login)
echo -e "${COLOR_BLUE}Test 2: Create session (simulate login)${NC}"
# Note: Untuk test ini perlu login dulu. Uncomment jika sudah ada session
# curl -s -c cookies.txt "$BASE_URL/../login.php" \
#     -d "email=test@test.com&password=test123"
echo -e "Skipped - Requires manual login\n"

# Test 3: API request dengan filter status
echo -e "${COLOR_BLUE}Test 3: Filter by status parameter${NC}"
echo "Testing: status=borrowed"
# RESPONSE=$(curl -s -w "\n%{http_code}" "$BASE_URL/$API_ENDPOINT?status=borrowed" -b cookies.txt)
echo "Skipped - Requires session\n"

# Test 4: CSV Export
echo -e "${COLOR_BLUE}Test 4: CSV Export${NC}"
echo "Command to export CSV:"
echo "curl -s '$BASE_URL/$API_ENDPOINT?format=csv' > riwayat_peminjaman.csv"
echo "Skipped - Requires session\n"

# Test 5: Invalid parameter
echo -e "${COLOR_BLUE}Test 5: Invalid status parameter (expected 400)${NC}"
echo "Expected: Error message about invalid status"
echo "Skipped - Requires session\n"

# ============================================
# Manual Testing dengan PowerShell (Windows)
# ============================================

echo -e "${COLOR_BLUE}=== Manual Testing Steps ===${NC}\n"
echo "1. Open browser and login:"
echo "   http://localhost/perpustakaan-online/public/login.php"
echo ""
echo "2. Access the API in same browser:"
echo "   http://localhost/perpustakaan-online/public/api/borrowing-history.php"
echo ""
echo "3. Test filters:"
echo "   http://localhost/perpustakaan-online/public/api/borrowing-history.php?status=borrowed"
echo "   http://localhost/perpustakaan-online/public/api/borrowing-history.php?status=returned"
echo "   http://localhost/perpustakaan-online/public/api/borrowing-history.php?status=overdue"
echo ""
echo "4. Export to CSV:"
echo "   http://localhost/perpustakaan-online/public/api/borrowing-history.php?format=csv"
echo ""

# ============================================
# PowerShell Test Script
# ============================================

echo -e "${COLOR_BLUE}=== PowerShell Test Commands ===${NC}\n"
echo "Run this in PowerShell (Windows):"
echo ""
echo "# After login, test API:"
echo "\$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession"
echo "\$response = Invoke-WebRequest -Uri 'http://localhost/perpustakaan-online/public/api/borrowing-history.php' -WebSession \$session"
echo "\$response.Content | ConvertFrom-Json | ForEach-Object { \$_.data }"
echo ""

# ============================================
# Test Configuration Check
# ============================================

echo -e "${COLOR_BLUE}=== Configuration Check ===${NC}\n"

# Check if curl exists
if command -v curl &> /dev/null; then
    echo -e "${COLOR_GREEN}✓${NC} curl installed"
else
    echo -e "${COLOR_RED}✗${NC} curl NOT installed"
fi

# Check if php exists
if command -v php &> /dev/null; then
    echo -e "${COLOR_GREEN}✓${NC} PHP installed"
    php -v | head -1
else
    echo -e "${COLOR_RED}✗${NC} PHP NOT installed"
fi

# Check if MySQL is running
if nc -z localhost 3306 2>/dev/null; then
    echo -e "${COLOR_GREEN}✓${NC} MySQL/MariaDB is running"
else
    echo -e "${COLOR_RED}✗${NC} MySQL/MariaDB is NOT running"
fi

# Check if Apache/Web server is running
if curl -s -o /dev/null -w "%{http_code}" http://localhost > /dev/null; then
    echo -e "${COLOR_GREEN}✓${NC} Web server is running"
else
    echo -e "${COLOR_RED}✗${NC} Web server is NOT running"
fi

echo ""
echo -e "${COLOR_BLUE}=== Test Complete ===${NC}"
