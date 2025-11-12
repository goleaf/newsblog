#!/bin/bash

# Nova error monitoring script
# Parses logs for Nova-related errors and reports critical issues

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
LOG_FILE="$PROJECT_ROOT/storage/logs/laravel.log"
NOVA_LOG_FILE="$PROJECT_ROOT/storage/logs/nova.log"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
ALERT_THRESHOLD=5  # Number of errors to trigger alert

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

cd "$PROJECT_ROOT"

echo "[$TIMESTAMP] Checking Nova error logs..." | tee -a "$PROJECT_ROOT/storage/logs/nova-error-check.log"

ERROR_COUNT=0
CRITICAL_ERRORS=0

# Check main Laravel log for Nova-related errors
if [ -f "$LOG_FILE" ]; then
    echo "Scanning main log file: $LOG_FILE"
    
    # Authentication failures
    AUTH_FAILURES=$(grep -i "nova" "$LOG_FILE" 2>/dev/null | grep -i "authentication\|unauthorized\|401" | grep -c "$(date '+%Y-%m-%d')" || echo "0")
    if [ "$AUTH_FAILURES" -gt 0 ]; then
        echo -e "${YELLOW}⚠${NC} Found $AUTH_FAILURES Nova authentication failures today"
        ERROR_COUNT=$((ERROR_COUNT + AUTH_FAILURES))
    fi
    
    # Authorization errors
    AUTHZ_ERRORS=$(grep -i "nova" "$LOG_FILE" 2>/dev/null | grep -i "authorization\|forbidden\|403\|permission" | grep -c "$(date '+%Y-%m-%d')" || echo "0")
    if [ "$AUTHZ_ERRORS" -gt 0 ]; then
        echo -e "${YELLOW}⚠${NC} Found $AUTHZ_ERRORS Nova authorization errors today"
        ERROR_COUNT=$((ERROR_COUNT + AUTHZ_ERRORS))
    fi
    
    # 500 errors
    SERVER_ERRORS=$(grep -i "nova" "$LOG_FILE" 2>/dev/null | grep -E "500|Internal Server Error|exception" | grep -c "$(date '+%Y-%m-%d')" || echo "0")
    if [ "$SERVER_ERRORS" -gt 0 ]; then
        echo -e "${RED}✗${NC} Found $SERVER_ERRORS Nova server errors (500) today"
        ERROR_COUNT=$((ERROR_COUNT + SERVER_ERRORS))
        CRITICAL_ERRORS=$((CRITICAL_ERRORS + SERVER_ERRORS))
    fi
    
    # Database errors
    DB_ERRORS=$(grep -i "nova" "$LOG_FILE" 2>/dev/null | grep -i "database\|sql\|query.*failed\|connection" | grep -c "$(date '+%Y-%m-%d')" || echo "0")
    if [ "$DB_ERRORS" -gt 0 ]; then
        echo -e "${RED}✗${NC} Found $DB_ERRORS Nova database errors today"
        ERROR_COUNT=$((ERROR_COUNT + DB_ERRORS))
        CRITICAL_ERRORS=$((CRITICAL_ERRORS + DB_ERRORS))
    fi
    
    # License errors
    LICENSE_ERRORS=$(grep -i "nova" "$LOG_FILE" 2>/dev/null | grep -i "license\|invalid.*key" | grep -c "$(date '+%Y-%m-%d')" || echo "0")
    if [ "$LICENSE_ERRORS" -gt 0 ]; then
        echo -e "${RED}✗${NC} Found $LICENSE_ERRORS Nova license errors today"
        ERROR_COUNT=$((ERROR_COUNT + LICENSE_ERRORS))
        CRITICAL_ERRORS=$((CRITICAL_ERRORS + LICENSE_ERRORS))
    fi
else
    echo -e "${YELLOW}⚠${NC} Log file not found: $LOG_FILE"
fi

# Check Nova-specific log file if it exists
if [ -f "$NOVA_LOG_FILE" ]; then
    echo "Scanning Nova log file: $NOVA_LOG_FILE"
    NOVA_ERRORS=$(grep -i "error\|exception\|failed" "$NOVA_LOG_FILE" 2>/dev/null | grep -c "$(date '+%Y-%m-%d')" || echo "0")
    if [ "$NOVA_ERRORS" -gt 0 ]; then
        echo -e "${YELLOW}⚠${NC} Found $NOVA_ERRORS errors in Nova log today"
        ERROR_COUNT=$((ERROR_COUNT + NOVA_ERRORS))
    fi
fi

# Summary
echo ""
echo "=== Error Summary ==="
echo "Total Nova-related errors today: $ERROR_COUNT"
echo "Critical errors: $CRITICAL_ERRORS"

if [ "$ERROR_COUNT" -eq 0 ]; then
    echo -e "${GREEN}✓${NC} No Nova errors found today"
    exit 0
elif [ "$CRITICAL_ERRORS" -gt 0 ]; then
    echo -e "${RED}✗${NC} CRITICAL: $CRITICAL_ERRORS critical errors detected"
    exit 1
elif [ "$ERROR_COUNT" -ge "$ALERT_THRESHOLD" ]; then
    echo -e "${YELLOW}⚠${NC} ALERT: $ERROR_COUNT errors detected (threshold: $ALERT_THRESHOLD)"
    exit 1
else
    echo -e "${YELLOW}⚠${NC} Non-critical errors detected: $ERROR_COUNT"
    exit 0
fi

