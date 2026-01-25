#!/usr/bin/env bash

################################################################################
# UptimeSentinel Performance Monitor
# 
# Purpose: This script provides a real-time "dashboard" of the system's 
#          throughput. It compares the work pending in RabbitMQ with the 
#          results collected in Redis to measure overall system speed (RPM).
################################################################################

# --- STYLING (Colors & ANSI Codes) ---
CYAN='\033[0;36m'
GOLD='\033[0;33m'
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'
BOLD='\033[1m'
CLEAR_LINE='\033[K'

# --- CONFIGURATION (Connection Details) ---
MQ_URL="http://localhost:15672/api/queues/%2f/messages"
MQ_USER="anar"
MQ_PASS="1234"
REDIS_CONTAINER="uptime-sentinel-redis-1"
INTERVAL=2 # Refresh every 2 seconds

# --- STATE TRACKING (To calculate rates) ---
PREV_MQ=0
PREV_REDIS=0
FIRST_RUN=1

# Clear the screen once at startup
clear
printf "${CYAN}${BOLD}🚀 UptimeSentinel Performance Monitor${NC}\n"
printf "Press [CTRL+C] to stop\n\n"

while true; do
    # 1. FETCH RABBITMQ METRICS
    # We ask RabbitMQ API for the current count of messages in the queue
    MQ_DATA=$(curl -s -u "${MQ_USER}:${MQ_PASS}" "${MQ_URL}")
    MQ_READY=$(echo "$MQ_DATA" | python3 -c "import sys,json; print(json.load(sys.stdin).get('messages_ready', 0))" 2>/dev/null || echo 0)
    MQ_UNACK=$(echo "$MQ_DATA" | python3 -c "import sys,json; print(json.load(sys.stdin).get('messages_unacknowledged', 0))" 2>/dev/null || echo 0)
    
    # 2. FETCH REDIS METRICS
    # We ask Redis for the size of the telemetry results list
    REDIS_COUNT=$(docker exec "${REDIS_CONTAINER}" redis-cli LLEN telemetry_buffer 2>/dev/null || echo 0)
    
    # 3. FETCH WORKER STATUS
    # We get worker container statuses as JSON for reliable multi-version parsing
    WORKER_PS=$(docker compose ps worker --format json 2>/dev/null || echo "[]")
    
    # 4. CALCULATE PERFORMANCE RATES
    if [ $FIRST_RUN -eq 1 ]; then
        MQ_RATE="calculating..."
        REDIS_RATE="calculating..."
        RPM_DISPLAY="calculating..."
        FIRST_RUN=0
    else
        # Compare current values with values from 2 seconds ago
        MQ_DIFF=$((MQ_READY - PREV_MQ))
        REDIS_DIFF=$((REDIS_COUNT - PREV_REDIS))
        # RPM = (Results in 2s / 2) * 60 seconds = Results * 30
        REDIS_RPM=$((REDIS_DIFF * 30))
        
        # Format the backlog change (Negative is good = clearing work)
        if [ "$MQ_DIFF" -lt 0 ]; then
            MQ_RATE="${GREEN}🔻 ${MQ_DIFF} batches${NC}"
        elif [ "$MQ_DIFF" -gt 0 ]; then
            MQ_RATE="${GOLD}🔺 +${MQ_DIFF}${NC}"
        else
            MQ_RATE="${NC}➖ No change${NC}"
        fi

        # Format the check velocity and RPM
        if [ "$REDIS_DIFF" -gt 0 ]; then 
            REDIS_RATE="${GREEN}⚡ +${REDIS_DIFF} results${NC}"
            RPM_DISPLAY="${BOLD}${GREEN}${REDIS_RPM} RPM${NC}"
        else 
            REDIS_RATE="${RED}⏸️  Stalled${NC}"
            RPM_DISPLAY="${RED}0 RPM${NC}"
        fi
    fi

    # 5. RENDER THE UI
    # We move the cursor back to the fixed starting point instead of clearing 
    # the screen to prevent flickering.
    printf "\033[3;1H" 
    
    printf "${BOLD}--- 🕰️  Snapshot: $(date +%H:%M:%S) ---${NC}${CLEAR_LINE}\n\n"
    
    # Display RabbitMQ Section
    printf "${CYAN}📥 RABBITMQ (The Backlog)${NC}${CLEAR_LINE}\n"
    printf "  Queued Batches:  ${BOLD}%s${NC}${CLEAR_LINE}\n" "$(printf "%'d" "$MQ_READY")"
    printf "  In Worker Hands: %d${CLEAR_LINE}\n" "$MQ_UNACK"
    printf "  Backlog Change:  %b / %ds${CLEAR_LINE}\n" "$MQ_RATE" "$INTERVAL"
    
    # Display Redis Section
    printf "\n${GOLD}🧠 REDIS (The Results Buffer)${NC}${CLEAR_LINE}\n"
    printf "  Total Items:     ${BOLD}%s${NC}${CLEAR_LINE}\n" "$(printf "%'d" "$REDIS_COUNT")"
    printf "  Check Velocity:  %b / %ds${CLEAR_LINE}\n" "$REDIS_RATE" "$INTERVAL"
    printf "  System Speed:    %b${CLEAR_LINE}\n" "$RPM_DISPLAY"
    
    # Display Worker Section (Parsing JSON list with Python)
    printf "\n${GREEN}👷 WORKER FLEET${NC}${CLEAR_LINE}\n"
    echo "$WORKER_PS" | python3 -c "
import sys, json
try:
    data = json.load(sys.stdin)
    if isinstance(data, dict): data = [data]
    for w in data[:5]:
        print(f'  {w.get(\"Name\")}: {w.get(\"Status\")}')
except:
    print('  Waiting for workers...')
"
    printf "${CLEAR_LINE}"
    
    printf "\n${CYAN}---------------------------------------${NC}${CLEAR_LINE}\n"
    
    # 6. SAVE CURRENT STATE FOR NEXT LOOP
    PREV_MQ=$MQ_READY
    PREV_REDIS=$REDIS_COUNT
    sleep $INTERVAL
done
