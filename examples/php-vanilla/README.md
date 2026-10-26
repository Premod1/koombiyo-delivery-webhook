# Vanilla PHP — Koombiyo Webhook Receiver

A single-file, zero-dependency PHP script that can be uploaded to any shared hosting, cPanel, or VPS running PHP 7.4+.

## File Overview
- `webhook.php`: Reads the raw JSON body from `php://input`, logs the payload, validates required fields, flushes an HTTP `200 OK` response, and updates your MySQL database.

## How to Deploy

### Option 1: Shared Hosting (cPanel / Apache)
1. Upload `webhook.php` to your `public_html/` or `api/` folder (e.g., `https://yourdomain.com/webhook.php`).
2. Make sure PHP has write permissions to create `webhook.log` in that directory.
3. Open your **Koombiyo Client Portal**, navigate to **Settings > Webhook URL**, and enter:
   ```text
   https://yourdomain.com/webhook.php
   ```

### Option 2: Local Testing with PHP Built-in Server
1. In this directory, start PHP's built-in server:
   ```bash
   php -S localhost:8000
   ```
2. Start an ngrok tunnel:
   ```bash
   ngrok http 8000
   ```
3. Use the ngrok HTTPS URL:
   ```text
   https://xxxx.ngrok-free.app/webhook.php
   ```

### Option 3: Test with cURL
```bash
curl -X POST http://localhost:8000/webhook.php \
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
Check `webhook.log` in the same directory to verify the logged data.
