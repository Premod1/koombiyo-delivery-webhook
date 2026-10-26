#!/usr/bin/env bash

# Test script to simulate a Koombiyo Webhook payload
# Usage:
#   ./test-webhook.sh [TARGET_URL]
# Example:
#   ./test-webhook.sh http://localhost:3000/api/webhooks/koombiyo
#   ./test-webhook.sh http://localhost:8000/webhook.php

TARGET_URL="${1:-http://localhost:3000/api/webhooks/koombiyo}"

echo "=========================================================="
echo "🚚 Testing Koombiyo Delivery Webhook"
echo "Target Endpoint: ${TARGET_URL}"
echo "=========================================================="

PAYLOAD='{
  "waybill_id": "102938475",
  "status_id": "5",
  "status": "Delivered",
  "weight": "1.25",
  "status_date": "'$(date +"%Y-%m-%d %H:%M:%S")'",
  "del_charge": "350.00",
  "reason": "Delivered to customer"
}'

echo ""
echo "Sending Payload:"
echo "${PAYLOAD}"
echo ""

RESPONSE=$(curl -s -w "\nHTTP_STATUS_CODE:%{http_code}" -X POST "${TARGET_URL}" \
  -H "Content-Type: application/json" \
  -d "${PAYLOAD}")

BODY=$(echo "${RESPONSE}" | sed -e '$d')
HTTP_STATUS=$(echo "${RESPONSE}" | tail -n1 | cut -d':' -f2)

echo "----------------------------------------------------------"
echo "Response Status Code : ${HTTP_STATUS}"
echo "Response Body        : ${BODY}"
echo "----------------------------------------------------------"

if [ "${HTTP_STATUS}" -eq 200 ]; then
  echo "✅ SUCCESS: The endpoint returned HTTP 200 OK!"
else
  echo "❌ WARNING: Expected HTTP 200, but received HTTP ${HTTP_STATUS}."
fi
echo "=========================================================="
