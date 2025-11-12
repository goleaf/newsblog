#!/bin/bash

# Daily Nova performance report generator
# Generates a comprehensive daily report of Nova usage and performance

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
REPORT_DIR="$PROJECT_ROOT/storage/reports"
REPORT_FILE="$REPORT_DIR/nova-daily-report-$(date +%Y%m%d).txt"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

mkdir -p "$REPORT_DIR"

cd "$PROJECT_ROOT"

echo "Generating Nova daily performance report..."
echo "Report will be saved to: $REPORT_FILE"

{
    echo "=========================================="
    echo "Nova Daily Performance Report"
    echo "Date: $(date '+%Y-%m-%d')"
    echo "Generated: $TIMESTAMP"
    echo "=========================================="
    echo ""
    
    # Overall Statistics
    echo "=== OVERALL STATISTICS ==="
    echo ""
    
    # Get performance metrics
    if php artisan nova:monitor-performance --json > /tmp/nova-metrics.json 2>/dev/null; then
        echo "Performance Metrics:"
        cat /tmp/nova-metrics.json | php -r 'echo json_encode(json_decode(file_get_contents("php://stdin")), JSON_PRETTY_PRINT);'
        echo ""
    else
        echo "Performance metrics not available"
        echo ""
    fi
    
    # Resource Access Summary
    echo "=== RESOURCE ACCESS SUMMARY ==="
    echo ""
    
    # Count posts created today
    POSTS_TODAY=$(php artisan tinker --execute="echo App\Models\Post::whereDate('created_at', today())->count();" 2>/dev/null || echo "N/A")
    echo "Posts created today: $POSTS_TODAY"
    
    # Count users created today
    USERS_TODAY=$(php artisan tinker --execute="echo App\Models\User::whereDate('created_at', today())->count();" 2>/dev/null || echo "N/A")
    echo "Users created today: $USERS_TODAY"
    
    # Count comments approved today
    COMMENTS_APPROVED=$(php artisan tinker --execute="echo App\Models\Comment::whereDate('updated_at', today())->where('status', 'approved')->count();" 2>/dev/null || echo "N/A")
    echo "Comments approved today: $COMMENTS_APPROVED"
    
    echo ""
    
    # Error Summary
    echo "=== ERROR SUMMARY ==="
    echo ""
    
    # Count errors from logs
    if [ -f "$PROJECT_ROOT/storage/logs/laravel.log" ]; then
        ERROR_COUNT=$(grep -i "nova" "$PROJECT_ROOT/storage/logs/laravel.log" 2>/dev/null | grep -i "error\|exception" | grep -c "$(date '+%Y-%m-%d')" || echo "0")
        echo "Total Nova errors today: $ERROR_COUNT"
        
        AUTH_ERRORS=$(grep -i "nova" "$PROJECT_ROOT/storage/logs/laravel.log" 2>/dev/null | grep -i "authentication\|unauthorized\|401" | grep -c "$(date '+%Y-%m-%d')" || echo "0")
        echo "Authentication errors: $AUTH_ERRORS"
        
        AUTHZ_ERRORS=$(grep -i "nova" "$PROJECT_ROOT/storage/logs/laravel.log" 2>/dev/null | grep -i "authorization\|forbidden\|403" | grep -c "$(date '+%Y-%m-%d')" || echo "0")
        echo "Authorization errors: $AUTHZ_ERRORS"
        
        SERVER_ERRORS=$(grep -i "nova" "$PROJECT_ROOT/storage/logs/laravel.log" 2>/dev/null | grep -E "500|Internal Server Error" | grep -c "$(date '+%Y-%m-%d')" || echo "0")
        echo "Server errors (500): $SERVER_ERRORS"
    else
        echo "Log file not found"
    fi
    
    echo ""
    
    # Performance Trends
    echo "=== PERFORMANCE TRENDS ==="
    echo ""
    
    # Check for slow queries
    if [ -f "$PROJECT_ROOT/storage/logs/query.log" ]; then
        SLOW_QUERIES=$(grep -i "nova" "$PROJECT_ROOT/storage/logs/query.log" 2>/dev/null | grep -E "time.*[1-9][0-9]{2,}" | grep -c "$(date '+%Y-%m-%d')" || echo "0")
        echo "Slow queries (>100ms) today: $SLOW_QUERIES"
    else
        echo "Query log not available"
    fi
    
    # Memory usage
    MEMORY_USAGE=$(php -r "echo round(memory_get_usage(true) / 1024 / 1024, 2);")
    echo "Current memory usage: ${MEMORY_USAGE}MB"
    
    echo ""
    
    # Queue Status
    echo "=== QUEUE STATUS ==="
    echo ""
    
    FAILED_JOBS=$(php artisan queue:failed 2>/dev/null | wc -l || echo "0")
    if [ "$FAILED_JOBS" -gt 1 ]; then
        FAILED_COUNT=$((FAILED_JOBS - 1))
        echo "Failed queue jobs: $FAILED_COUNT"
    else
        echo "Failed queue jobs: 0"
    fi
    
    echo ""
    
    # Most Accessed Resources
    echo "=== MOST ACCESSED RESOURCES ==="
    echo ""
    echo "Note: Resource access tracking requires additional implementation"
    echo "Consider adding access logging for detailed analytics"
    
    echo ""
    
    # Recommendations
    echo "=== RECOMMENDATIONS ==="
    echo ""
    
    if [ "$ERROR_COUNT" -gt 10 ]; then
        echo "⚠️  High error count detected. Review error logs for patterns."
    fi
    
    if [ "$SLOW_QUERIES" -gt 5 ]; then
        echo "⚠️  Multiple slow queries detected. Consider query optimization."
    fi
    
    if [ "$FAILED_COUNT" -gt 0 ]; then
        echo "⚠️  Failed queue jobs detected. Review queue:failed for details."
    fi
    
    if [ "$ERROR_COUNT" -eq 0 ] && [ "$SLOW_QUERIES" -eq 0 ] && [ "$FAILED_COUNT" -eq 0 ]; then
        echo "✓ All systems operating normally"
    fi
    
    echo ""
    echo "=========================================="
    echo "Report generated: $TIMESTAMP"
    echo "=========================================="
    
} > "$REPORT_FILE"

echo "Daily report generated successfully!"
echo "Report saved to: $REPORT_FILE"

# Optionally, send report via email or webhook
# Uncomment and configure if needed:
# mail -s "Nova Daily Report $(date +%Y-%m-%d)" admin@example.com < "$REPORT_FILE"

