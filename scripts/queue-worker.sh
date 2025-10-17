#!/bin/bash

# Queue Worker Script for Shared Hosting
# This script provides better error handling and logging for queue workers

# Configuration
PROJECT_PATH="/Applications/MAMP/htdocs/shared-host-resource-usage"
LOG_FILE="$PROJECT_PATH/storage/logs/queue-worker.log"
MAX_RUNTIME=300  # 5 minutes
MEMORY_LIMIT=128 # MB

# Change to project directory
cd "$PROJECT_PATH" || exit 1

# Log start time
echo "$(date): Starting queue worker" >> "$LOG_FILE"

# Run queue worker with timeout
timeout $MAX_RUNTIME php artisan queue:work \
    --timeout=300 \
    --tries=3 \
    --max-time=300 \
    --memory=$MEMORY_LIMIT \
    --sleep=3 \
    --max-jobs=10 \
    --stop-when-empty \
    --verbose >> "$LOG_FILE" 2>&1

# Log completion
echo "$(date): Queue worker completed" >> "$LOG_FILE"
