#!/bin/bash

# Nova performance monitoring script
# Checks response times, error rates, database queries, memory usage, and queue processing

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
LOG_FILE="$PROJECT_ROOT/storage/logs/nova-monitoring.log"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

cd "$PROJECT_ROOT"

echo "[$TIMESTAMP] Starting Nova monitoring check..." | tee -a "$LOG_FILE"

# Check if Nova routes are accessible
echo "Checking Nova routes..."
if php artisan route:list | grep -q "nova"; then
    echo -e "${GREEN}✓${NC} Nova routes registered" | tee -a "$LOG_FILE"
else
    echo -e "${RED}✗${NC} Nova routes not found" | tee -a "$LOG_FILE"
fi

# Run performance monitoring command
echo "Collecting performance metrics..."
if php artisan nova:monitor-performance --json > /tmp/nova-metrics.json 2>/dev/null; then
    METRICS=$(cat /tmp/nova-metrics.json)
    echo "Performance metrics collected:" | tee -a "$LOG_FILE"
    echo "$METRICS" | tee -a "$LOG_FILE"
else
    echo -e "${YELLOW}⚠${NC} Performance monitoring command not available or failed" | tee -a "$LOG_FILE"
fi

# Check error logs for Nova-related errors (last hour)
echo "Checking error logs..."
ERROR_COUNT=$(grep -i "nova\|Nova" "$PROJECT_ROOT/storage/logs/laravel.log" 2>/dev/null | grep -i "error\|exception\|failed" | grep -c "$(date '+%Y-%m-%d %H')" || echo "0")
if [ "$ERROR_COUNT" -gt 0 ]; then
    echo -e "${RED}⚠${NC} Found $ERROR_COUNT Nova-related errors in the last hour" | tee -a "$LOG_FILE"
else
    echo -e "${GREEN}✓${NC} No Nova-related errors found in the last hour" | tee -a "$LOG_FILE"
fi

# Check database query performance (if query log exists)
if [ -f "$PROJECT_ROOT/storage/logs/query.log" ]; then
    SLOW_QUERIES=$(grep -i "nova\|nova_" "$PROJECT_ROOT/storage/logs/query.log" 2>/dev/null | grep -E "time.*[1-9][0-9]{2,}" | wc -l || echo "0")
    if [ "$SLOW_QUERIES" -gt 0 ]; then
        echo -e "${YELLOW}⚠${NC} Found $SLOW_QUERIES slow Nova queries (>100ms)" | tee -a "$LOG_FILE"
    fi
fi

# Check memory usage
MEMORY_USAGE=$(php -r "echo memory_get_usage(true) / 1024 / 1024;")
echo "Current PHP memory usage: ${MEMORY_USAGE}MB" | tee -a "$LOG_FILE"

# Check queue processing status
if php artisan queue:monitor --help > /dev/null 2>&1; then
    QUEUE_SIZE=$(php artisan queue:monitor default 2>/dev/null | tail -n 1 | awk '{print $NF}' || echo "N/A")
    echo "Queue size: $QUEUE_SIZE" | tee -a "$LOG_FILE"
else
    FAILED_JOBS=$(php artisan queue:failed 2>/dev/null | wc -l || echo "0")
    if [ "$FAILED_JOBS" -gt 1 ]; then
        echo -e "${YELLOW}⚠${NC} Found $((FAILED_JOBS - 1)) failed queue jobs" | tee -a "$LOG_FILE"
    else
        echo -e "${GREEN}✓${NC} No failed queue jobs" | tee -a "$LOG_FILE"
    fi
fi

# Check disk space
DISK_USAGE=$(df -h "$PROJECT_ROOT" | tail -n 1 | awk '{print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 90 ]; then
    echo -e "${RED}⚠${NC} Disk usage is at ${DISK_USAGE}%" | tee -a "$LOG_FILE"
else
    echo "Disk usage: ${DISK_USAGE}%" | tee -a "$LOG_FILE"
fi

echo "[$TIMESTAMP] Monitoring check complete" | tee -a "$LOG_FILE"
echo "---" | tee -a "$LOG_FILE"

