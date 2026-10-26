# Node.js Express — Koombiyo Webhook Receiver

A minimal, production-ready webhook receiver for **Koombiyo Delivery** built with Express.

## Features
- Validates the incoming Koombiyo payload (`waybill_id`, `status_id`, `status`, etc.).
- Immediately returns an HTTP `200 OK` response within `< 50ms` (preventing Koombiyo retry timeouts).
- Processes business logic asynchronously using `setImmediate()`.
- Implements out-of-order date checking to prevent overwriting newer states.
- Clean status mapping (Delivered, Out for Delivery, Return Pending, etc.).

## Quick Start

### 1. Install Dependencies
```bash
npm install
```

### 2. Start the Server
```bash
npm start
```
By default, the server runs on `http://localhost:3000`.

### 3. Expose via Ngrok for Local Testing
Koombiyo's servers cannot reach your `localhost`. Expose your local port with ngrok:
```bash
ngrok http 3000
```
Then copy the HTTPS forwarding URL (e.g. `https://xxxx.ngrok-free.app/api/webhooks/koombiyo`) and save it in your **Koombiyo Client Portal** Settings.

### 4. Test with cURL
You can send a test payload directly from your terminal:
```bash
curl -X POST http://localhost:3000/api/webhooks/koombiyo \
  -H "Content-Type: application/json" \
  -d '{
    "waybill_id": "102938475",
    "status_id": "5",
    "status": "Delivered",
    "weight": "1.25",
    "status_date": "2026-10-26 14:32:00",
    "del_charge": "350.00",
    "reason": "Delivered to customer"
  }'
```
Expected response:
```json
{
  "status": "success",
  "message": "Webhook received successfully"
}
```
