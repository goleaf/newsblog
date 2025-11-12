#!/bin/bash

# Nova Deployment Monitoring Script
# This script helps monitor the Nova integration during the 48-hour post-deployment period

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
LOG_FILE="storage/logs/laravel.log"
MONITORING_LOG="storage/logs/nova-monitoring-$(date +%Y%m%d).log"
ERROR_THRESHOLD=10
SLOW_QUERY_THRESHOLD=1000 # milliseconds

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Nova Deployment Monitoring${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo "Monitoring started at: $(date)"
echo "Logging to: $MONITORING_LOG"
echo ""

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$MONITORING_LOG"
}

# Function to check application health
check_application_health() {
    echo -e "${BLUE}1. Checking Application Health...${NC}"
    
    # Check if application is responding
    if php artisan --version > /dev/null 2>&1; then
        echo -e "${GREEN}   ✓ Application is responding${NC}"
        log_message "INFO: Application health check passed"
    else
        echo -e "${RED}   ✗ Application is not responding${NC}"
        log_message "ERROR: Application health check failed"
        return 1
    fi
    
    # Check database connection
    if php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; then
        echo -e "${GREEN}   ✓ Database connection successful${NC}"
        log_message "INFO: Database connection check passed"
    else
        echo -e "${RED}   ✗ Database connection failed${NC}"
        log_message "ERROR: Database connection check failed"
        return 1
    fi
    
    echo ""
}

# Function to check Nova-specific health
check_nova_health() {
    echo -e "${BLUE}2. Checking Nova Health...${NC}"
    
    # Check if Nova routes are registered
    if php artisan route:list | grep -q "nova"; then
        echo -e "${GREEN}   ✓ Nova routes registered${NC}"
        log_message "INFO: Nova routes check passed"
    else
        echo -e "${RED}   ✗ Nova routes not found${NC}"
        log_message "ERROR: Nova routes check failed"
        return 1
    fi
    
    # Check if Nova assets exist
    if [ -d "public/vendor/nova" ]; then
        echo -e "${GREEN}   ✓ Nova assets published${NC}"
        log_message "INFO: Nova assets check passed"
    else
        echo -e "${YELLOW}   ⚠ Nova assets not found${NC}"
        log_message "WARNING: Nova assets not found"
    fi
    
    echo ""
}

# Function to check error logs
check_error_logs() {
    echo -e "${BLUE}3. Checking Error Logs...${NC}"
    
    if [ ! -f "$LOG_FILE" ]; then
        echo -e "${YELLOW}   ⚠ Log file not found${NC}"
        log_message "WARNING: Log file not found"
        return 0
    fi
    
    # Count errors in last hour
    ERROR_COUNT=$(grep -c "ERROR" "$LOG_FILE" 2>/dev/null | tail -1000 || echo "0")
    
    if [ "$ERROR_COUNT" -eq 0 ]; then
        echo -e "${GREEN}   ✓ No errors in recent logs${NC}"
        log_message "INFO: No errors found in recent logs"
    elif [ "$ERROR_COUNT" -lt "$ERROR_THRESHOLD" ]; then
        echo -e "${YELLOW}   ⚠ $ERROR_COUNT errors found (below threshold)${NC}"
        log_message "WARNING: $ERROR_COUNT errors found"
    else
        echo -e "${RED}   ✗ $ERROR_COUNT errors found (above threshold)${NC}"
        log_message "ERROR: $ERROR_COUNT errors found (threshold: $ERROR_THRESHOLD)"
        
        # Show recent errors
        echo -e "${YELLOW}   Recent errors:${NC}"
        grep "ERROR" "$LOG_FILE" | tail -5
    fi
    
    # Check for Nova-specific errors
    NOVA_ERROR_COUNT=$(grep -i "nova" "$LOG_FILE" | grep -c "ERROR" 2>/dev/null || echo "0")
    
    if [ "$NOVA_ERROR_COUNT" -eq 0 ]; then
        echo -e "${GREEN}   ✓ No Nova-specific errors${NC}"
        log_message "INFO: No Nova-specific errors found"
    else
        echo -e "${YELLOW}   ⚠ $NOVA_ERROR_COUNT Nova-specific errors found${NC}"
        log_message "WARNING: $NOVA_ERROR_COUNT Nova-specific errors found"
        
        # Show Nova errors
        echo -e "${YELLOW}   Recent Nova errors:${NC}"
        grep -i "nova" "$LOG_FILE" | grep "ERROR" | tail -3
    fi
    
    echo ""
}

# Function to check performance
check_performance() {
    echo -e "${BLUE}4. Checking Performance...${NC}"
    
    # Check disk usage
    DISK_USAGE=$(df -h . | awk 'NR==2 {print $5}' | sed 's/%//')
    
    if [ "$DISK_USAGE" -lt 80 ]; then
        echo -e "${GREEN}   ✓ Disk usage: ${DISK_USAGE}%${NC}"
        log_message "INFO: Disk usage at ${DISK_USAGE}%"
    elif [ "$DISK_USAGE" -lt 90 ]; then
        echo -e "${YELLOW}   ⚠ Disk usage: ${DISK_USAGE}% (warning)${NC}"
        log_message "WARNING: Disk usage at ${DISK_USAGE}%"
    else
        echo -e "${RED}   ✗ Disk usage: ${DISK_USAGE}% (critical)${NC}"
        log_message "ERROR: Disk usage at ${DISK_USAGE}%"
    fi
    
    # Check storage directory size
    STORAGE_SIZE=$(du -sh storage 2>/dev/null | awk '{print $1}')
    echo -e "   Storage directory size: $STORAGE_SIZE"
    log_message "INFO: Storage directory size: $STORAGE_SIZE"
    
    # Check log file size
    if [ -f "$LOG_FILE" ]; then
        LOG_SIZE=$(du -sh "$LOG_FILE" 2>/dev/null | awk '{print $1}')
        echo -e "   Log file size: $LOG_SIZE"
        log_message "INFO: Log file size: $LOG_SIZE"
    fi
    
    echo ""
}

# Function to check queue status
check_queue_status() {
    echo -e "${BLUE}5. Checking Queue Status...${NC}"
    
    # Check for failed jobs
    FAILED_JOBS=$(php artisan queue:failed --json 2>/dev/null | grep -c "id" || echo "0")
    
    if [ "$FAILED_JOBS" -eq 0 ]; then
        echo -e "${GREEN}   ✓ No failed jobs${NC}"
        log_message "INFO: No failed jobs in queue"
    else
        echo -e "${YELLOW}   ⚠ $FAILED_JOBS failed jobs found${NC}"
        log_message "WARNING: $FAILED_JOBS failed jobs found"
    fi
    
    echo ""
}

# Function to check recent activity
check_recent_activity() {
    echo -e "${BLUE}6. Checking Recent Activity...${NC}"
    
    # Count recent activity logs (if activity_logs table exists)
    RECENT_ACTIVITY=$(php artisan tinker --execute="
        try {
            echo \App\Models\ActivityLog::where('created_at', '>=', now()->subHour())->count();
        } catch (\Exception \$e) {
            echo '0';
        }
    " 2>/dev/null || echo "0")
    
    echo -e "   Recent activity (last hour): $RECENT_ACTIVITY actions"
    log_message "INFO: $RECENT_ACTIVITY activities in last hour"
    
    echo ""
}

# Function to generate summary report
generate_summary() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}Monitoring Summary${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    
    # Count issues
    ERROR_COUNT=$(grep -c "ERROR" "$MONITORING_LOG" 2>/dev/null || echo "0")
    WARNING_COUNT=$(grep -c "WARNING" "$MONITORING_LOG" 2>/dev/null || echo "0")
    
    if [ "$ERROR_COUNT" -eq 0 ] && [ "$WARNING_COUNT" -eq 0 ]; then
        echo -e "${GREEN}✓ All checks passed - No issues detected${NC}"
        log_message "INFO: Monitoring completed successfully - No issues"
    elif [ "$ERROR_COUNT" -eq 0 ]; then
        echo -e "${YELLOW}⚠ $WARNING_COUNT warnings detected${NC}"
        log_message "INFO: Monitoring completed with $WARNING_COUNT warnings"
    else
        echo -e "${RED}✗ $ERROR_COUNT errors and $WARNING_COUNT warnings detected${NC}"
        log_message "ERROR: Monitoring completed with $ERROR_COUNT errors and $WARNING_COUNT warnings"
    fi
    
    echo ""
    echo "Monitoring completed at: $(date)"
    echo "Full log available at: $MONITORING_LOG"
    echo ""
}

# Function to show recommendations
show_recommendations() {
    echo -e "${BLUE}Recommendations:${NC}"
    echo ""
    
    # Check if there were errors
    ERROR_COUNT=$(grep -c "ERROR" "$MONITORING_LOG" 2>/dev/null || echo "0")
    
    if [ "$ERROR_COUNT" -gt 0 ]; then
        echo -e "${YELLOW}1. Review error logs immediately:${NC}"
        echo "   tail -100 $LOG_FILE | grep ERROR"
        echo ""
        echo -e "${YELLOW}2. Check Nova-specific errors:${NC}"
        echo "   grep -i 'nova' $LOG_FILE | grep ERROR"
        echo ""
    fi
    
    # Check disk usage
    DISK_USAGE=$(df -h . | awk 'NR==2 {print $5}' | sed 's/%//')
    if [ "$DISK_USAGE" -gt 80 ]; then
        echo -e "${YELLOW}3. Disk usage is high - consider cleanup:${NC}"
        echo "   php artisan cache:clear"
        echo "   php artisan view:clear"
        echo "   Find large log files: find storage/logs -type f -size +10M"
        echo ""
    fi
    
    # Check failed jobs
    FAILED_JOBS=$(php artisan queue:failed --json 2>/dev/null | grep -c "id" || echo "0")
    if [ "$FAILED_JOBS" -gt 0 ]; then
        echo -e "${YELLOW}4. Failed jobs detected - review and retry:${NC}"
        echo "   php artisan queue:failed"
        echo "   php artisan queue:retry all"
        echo ""
    fi
    
    echo -e "${BLUE}5. Continue monitoring regularly:${NC}"
    echo "   Run this script every 2-4 hours during the monitoring period"
    echo "   Review the full monitoring log: cat $MONITORING_LOG"
    echo ""
}

# Main execution
main() {
    log_message "INFO: Monitoring session started"
    
    check_application_health || true
    check_nova_health || true
    check_error_logs || true
    check_performance || true
    check_queue_status || true
    check_recent_activity || true
    
    generate_summary
    show_recommendations
    
    log_message "INFO: Monitoring session completed"
}

# Run main function
main

# Exit with appropriate code
ERROR_COUNT=$(grep -c "ERROR" "$MONITORING_LOG" 2>/dev/null || echo "0")
if [ "$ERROR_COUNT" -gt 0 ]; then
    exit 1
else
    exit 0
fi
