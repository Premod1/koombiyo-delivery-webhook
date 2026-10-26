<?php
/**
 * Koombiyo Delivery Webhook Receiver (Vanilla PHP)
 *
 * This standalone script can be placed on any PHP-supported server / cPanel hosting.
 * It receives webhook payloads from Koombiyo Delivery, validates them,
 * logs the event, and updates your database.
 */

// 1. Set response header to JSON
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

// 2. Read incoming raw JSON payload
$rawPayload = file_get_contents('php://input');
$data = json_decode($rawPayload, true);

// 3. Log raw payload for debugging / audit trail
$logEntry = sprintf(
    "[%s] Received Webhook: %s\n",
    date('Y-m-d H:i:s'),
    $rawPayload
);
file_put_contents(__DIR__ . '/webhook.log', $logEntry, FILE_APPEND);

// 4. Validate essential fields
if (!$data || !isset($data['waybill_id']) || !isset($data['status_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid payload: waybill_id, status_id, and status are required'
    ]);
    exit;
}

$waybillId  = trim((string)$data['waybill_id']);
$statusId   = (int)$data['status_id'];
$statusName = trim((string)$data['status']);
$statusDate = isset($data['status_date']) ? $data['status_date'] : date('Y-m-d H:i:s');
$delCharge  = isset($data['del_charge']) ? (float)$data['del_charge'] : 0.0;
$weight     = isset($data['weight']) ? (float)$data['weight'] : null;
$reason     = isset($data['reason']) ? trim((string)$data['reason']) : null;

// 5. CRITICAL: Always return HTTP 200 OK immediately
// Koombiyo marks delivery as failed and retries if response code is not 200.
http_response_code(200);
echo json_encode([
    'status'  => 'success',
    'message' => 'Webhook received successfully'
]);

// If PHP-FPM / FastCGI is available, flush response to client so Koombiyo finishes immediately
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

// 6. Database Update Logic (Example with PDO MySQL)
/*
try {
    $pdo = new PDO('mysql:host=localhost;dbname=your_ecommerce_db;charset=utf8mb4', 'db_user', 'db_password', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Check existing order/shipment
    $stmt = $pdo->prepare('SELECT id, status_id, last_status_date FROM orders WHERE waybill_id = :waybill_id LIMIT 1');
    $stmt->execute([':waybill_id' => $waybillId]);
    $order = $stmt->fetch();

    if ($order) {
        // Prevent out-of-order updates if an older webhook is re-delivered
        if (empty($order['last_status_date']) || strtotime($statusDate) >= strtotime($order['last_status_date'])) {
            
            // Map delivery status to order payment/fulfillment
            $paymentStatusUpdate = '';
            if ($statusId === 5) { // Delivered -> Mark Cash on Delivery as Paid
                $paymentStatusUpdate = ', payment_status = "paid"';
            }

            $updateStmt = $pdo->prepare("
                UPDATE orders 
                SET delivery_status_id = :status_id,
                    delivery_status_name = :status_name,
                    last_status_date = :status_date,
                    delivery_charge = :del_charge,
                    actual_weight = :weight,
                    status_reason = :reason,
                    updated_at = NOW()
                    {$paymentStatusUpdate}
                WHERE id = :id
            ");

            $updateStmt->execute([
                ':status_id'   => $statusId,
                ':status_name' => $statusName,
                ':status_date' => $statusDate,
                ':del_charge'  => $delCharge,
                ':weight'      => $weight,
                ':reason'      => $reason,
                ':id'          => $order['id'],
            ]);
        }
    }
} catch (Exception $e) {
    error_log('Database update failed: ' . $e->getMessage());
}
*/
