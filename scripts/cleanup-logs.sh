#!/bin/bash

# =====================================================
# Laravel Log Cleanup Script
# =====================================================
# Comprehensive log cleanup for Laravel application
# Handles Laravel logs, queue logs, and various error patterns
# Can be run manually or via cron
# =====================================================

# Set project directory (CHANGE THIS to your actual path)
PROJECT_DIR="/Applications/MAMP/htdocs/shared-host-resource-usage"
LOGS_DIR="$PROJECT_DIR/storage/logs"

# Navigate to project directory
cd $PROJECT_DIR || exit 1

echo "========================================"
echo "Laravel Log Cleanup & Management"
echo "========================================"
echo "Project: $PROJECT_DIR"
echo "Logs Dir: $LOGS_DIR"
echo ""

# Define log files to clean
LOG_FILES=(
    "$LOGS_DIR/laravel.log"
    "$LOGS_DIR/queue-worker.log"
    "$LOGS_DIR/queue.log"
)

# Option 1: Keep last N lines (default: 10000 for Laravel logs)
KEEP_LINES=${1:-10000}

# Option 2: Maximum log file size in MB (default: 50MB)
MAX_SIZE_MB=${2:-50}

echo "Configuration:"
echo "  Keep last $KEEP_LINES lines"
echo "  Max file size: ${MAX_SIZE_MB}MB"
echo ""

# Function to get file size in MB
get_file_size_mb() {
    local file="$1"
    local size=$(stat -f%z "$file" 2>/dev/null || stat -c%s "$file" 2>/dev/null || echo 0)
    echo $((size / 1048576))
}

# Function to count lines in file
count_lines() {
    local file="$1"
    wc -l < "$file" 2>/dev/null || echo 0
}

# Function to clean log file
clean_log_file() {
    local log="$1"
    local log_name=$(basename "$log")

    if [ ! -f "$log" ]; then
        echo "⚠️  File not found: $log_name"
        return
    fi

    local size_mb=$(get_file_size_mb "$log")
    local lines=$(count_lines "$log")

    echo "Processing: $log_name"
    echo "  Current size: ${size_mb}MB ($lines lines)"

    local needs_cleanup=false
    local reason=""

    # Check if file is too large
    if [ $size_mb -gt $MAX_SIZE_MB ]; then
        needs_cleanup=true
        reason="file size (${size_mb}MB > ${MAX_SIZE_MB}MB)"
    fi

    # Check if file has too many lines
    if [ $lines -gt $KEEP_LINES ]; then
        needs_cleanup=true
        reason="line count ($lines > $KEEP_LINES)"
    fi

    if [ "$needs_cleanup" = true ]; then
        echo "  🧹 Cleanup needed: $reason"

        # Create backup with timestamp
        local backup_file="${log}.backup.$(date +%Y%m%d_%H%M%S)"
        cp "$log" "$backup_file"
        echo "  📦 Backup created: $(basename $backup_file)"

        # Keep only last N lines
        tail -$KEEP_LINES "$log" > "$log.tmp" && mv "$log.tmp" "$log"

        # Get new size
        local new_size_mb=$(get_file_size_mb "$log")
        local new_lines=$(count_lines "$log")

        echo "  ✅ Cleaned! New size: ${new_size_mb}MB ($new_lines lines)"

        # Compress backup if it's large
        if [ $size_mb -gt 10 ]; then
            gzip "$backup_file" 2>/dev/null
            echo "  📦 Compressed backup: $(basename $backup_file).gz"
        fi
    else
        echo "  ✓ No cleanup needed"
    fi
    echo ""
}

# Clean main log files
echo "🧹 Cleaning main log files..."
for log in "${LOG_FILES[@]}"; do
    clean_log_file "$log"
done

# Clean up old backup files (older than 7 days)
echo "🗑️  Cleaning old backup files..."
find "$LOGS_DIR" -name "*.log.backup.*" -type f -mtime +7 -exec rm -f {} \; 2>/dev/null
find "$LOGS_DIR" -name "*.log.old" -type f -mtime +7 -exec rm -f {} \; 2>/dev/null
echo "✅ Removed backup files older than 7 days"

# Clean up compressed backups older than 30 days
find "$LOGS_DIR" -name "*.log.backup.*.gz" -type f -mtime +30 -exec rm -f {} \; 2>/dev/null
echo "✅ Removed compressed backups older than 30 days"

# Create log analysis summary
echo ""
echo "📊 Log Analysis Summary:"
echo "========================"

# Analyze Laravel log for common issues
if [ -f "$LOGS_DIR/laravel.log" ]; then
    local laravel_log="$LOGS_DIR/laravel.log"

    echo "Laravel Log Analysis:"
    echo "  Total lines: $(count_lines "$laravel_log")"
    echo "  File size: $(get_file_size_mb "$laravel_log")MB"

    # Count different types of errors
    local error_count=$(grep -c "ERROR" "$laravel_log" 2>/dev/null || echo 0)
    local warning_count=$(grep -c "WARNING" "$laravel_log" 2>/dev/null || echo 0)
    local info_count=$(grep -c "INFO" "$laravel_log" 2>/dev/null || echo 0)

    echo "  Errors: $error_count"
    echo "  Warnings: $warning_count"
    echo "  Info: $info_count"

    # Check for specific common issues
    local db_errors=$(grep -c "SQLSTATE\|Connection refused\|Base table or view not found" "$laravel_log" 2>/dev/null || echo 0)
    local job_errors=$(grep -c "Job.*failed\|Typed property.*must not be accessed" "$laravel_log" 2>/dev/null || echo 0)
    local resource_errors=$(grep -c "Resource usage check failed" "$laravel_log" 2>/dev/null || echo 0)

    if [ $db_errors -gt 0 ]; then
        echo "  ⚠️  Database errors: $db_errors"
    fi
    if [ $job_errors -gt 0 ]; then
        echo "  ⚠️  Job errors: $job_errors"
    fi
    if [ $resource_errors -gt 0 ]; then
        echo "  ⚠️  Resource check errors: $resource_errors"
    fi
fi

# Check disk space
echo ""
echo "💾 Disk Space Check:"
echo "==================="
df -h "$LOGS_DIR" 2>/dev/null || echo "Could not check disk space"

# Optional: Create log rotation if logrotate is available
if command -v logrotate >/dev/null 2>&1; then
    echo ""
    echo "🔄 Setting up log rotation..."

    # Create logrotate config for Laravel logs
    cat > /tmp/laravel-logrotate.conf << EOF
$LOGS_DIR/*.log {
    daily
    missingok
    rotate 7
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        /bin/kill -USR1 \$(cat /var/run/rsyslogd.pid 2>/dev/null) 2>/dev/null || true
    endscript
}
EOF

    echo "✅ Log rotation config created at /tmp/laravel-logrotate.conf"
    echo "   To install: sudo cp /tmp/laravel-logrotate.conf /etc/logrotate.d/laravel"
fi

echo ""
echo "========================================"
echo "✅ Cleanup Complete!"
echo "========================================"
echo ""
echo "💡 Tips:"
echo "  - Run this script daily via cron: 0 2 * * * /path/to/cleanup-logs.sh"
echo "  - Monitor log sizes regularly"
echo "  - Check for recurring errors in the analysis above"
echo "  - Consider setting up log rotation for production"

exit 0
