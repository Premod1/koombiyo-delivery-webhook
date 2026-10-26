import express from 'express';
import dotenv from 'dotenv';

dotenv.config();

const app = express();
const PORT = process.env.PORT || 3000;

// Koombiyo sends requests with Content-Type: application/json
app.use(express.json());

// In-memory mock database for demo purposes
const mockOrders = new Map([
  [
    '102938475',
    {
      orderId: 'ORD-5012',
      waybill_id: '102938475',
      customerName: 'Kamal Perera',
      customerPhone: '+94771234567',
      deliveryStatus: 'in_transit',
      paymentStatus: 'pending',
      amount: 4500.0,
      delCharge: 350.0,
      lastUpdated: '2026-10-26 10:00:00',
    },
  ],
]);

// Health check endpoint
app.get('/', (req, res) => {
  res.json({
    status: 'online',
    message: 'Koombiyo Delivery Webhook Receiver is running',
    timestamp: new Date().toISOString(),
  });
});

/**
 * Main Webhook Receiver Endpoint
 * URL configured in Koombiyo Merchant Portal:
 * https://yourdomain.com/api/webhooks/koombiyo
 */
app.post('/api/webhooks/koombiyo', (req, res) => {
  const payload = req.body;

  console.log('\n==================================================');
  console.log(`[${new Date().toISOString()}] 📦 Koombiyo Webhook Received:`);
  console.log(JSON.stringify(payload, null, 2));
  console.log('==================================================\n');

  // 1. Validate required fields
  const { waybill_id, status_id, status, status_date, del_charge, weight, reason } = payload;

  if (!waybill_id || status_id === undefined || !status) {
    console.error('❌ Validation Error: Missing required fields');
    return res.status(400).json({
      status: 'error',
      message: 'Invalid payload: waybill_id, status_id, and status are required',
    });
  }

  // 2. CRITICAL: Immediately acknowledge receipt to Koombiyo with HTTP 200 OK
  // Koombiyo requires a fast 200 response; timeouts or non-200 trigger retries.
  res.status(200).json({
    status: 'success',
    message: 'Webhook received successfully',
  });

  // 3. Process asynchronously (non-blocking)
  setImmediate(async () => {
    try {
      await processWebhookEvent({
        waybillId: String(waybill_id),
        statusId: Number(status_id),
        statusName: status,
        statusDate: status_date,
        delCharge: del_charge ? parseFloat(del_charge) : 0,
        weight: weight ? parseFloat(weight) : null,
        reason: reason || null,
      });
    } catch (err) {
      console.error(`❌ Error processing webhook for waybill #${waybill_id}:`, err);
    }
  });
});

/**
 * Handle business logic & database update asynchronously
 */
async function processWebhookEvent({
  waybillId,
  statusId,
  statusName,
  statusDate,
  delCharge,
  weight,
  reason,
}) {
  console.log(`⚡ Processing Waybill #${waybillId} -> Status: "${statusName}" (ID: ${statusId})`);

  const order = mockOrders.get(waybillId);

  if (!order) {
    console.warn(`⚠️ Warning: No existing order found with waybill #${waybillId}`);
    return;
  }

  // Guard against out-of-order events
  if (order.lastUpdated && new Date(statusDate) < new Date(order.lastUpdated)) {
    console.log(`ℹ️ Ignored older event (Event date: ${statusDate} vs Current: ${order.lastUpdated})`);
    return;
  }

  // Update order status based on Koombiyo Status Code
  switch (statusId) {
    case 5: // Delivered
      order.deliveryStatus = 'delivered';
      order.paymentStatus = 'paid'; // Cash on Delivery (COD) collected
      order.deliveredAt = statusDate;
      console.log(`🎉 Order ${order.orderId} marked as DELIVERED & COD Collected!`);
      // Here: Send SMS to customer, notify warehouse, update accounting
      break;

    case 4: // Out for Delivery
      order.deliveryStatus = 'out_for_delivery';
      console.log(`🚚 Order ${order.orderId} is OUT FOR DELIVERY`);
      break;

    case 9: // Pending Return
      order.deliveryStatus = 'return_pending';
      order.returnReason = reason || 'Customer unreachable / refused';
      console.log(`⚠️ Order ${order.orderId} marked as RETURN PENDING. Reason: ${reason}`);
      break;

    case 15: // Return Received at Merchant
      order.deliveryStatus = 'returned';
      order.paymentStatus = 'cancelled';
      console.log(`📦 Order ${order.orderId} returned to merchant warehouse.`);
      break;

    default:
      order.deliveryStatus = statusName.toLowerCase().replace(/\s+/g, '_');
      console.log(`🔄 Order ${order.orderId} updated to: ${order.deliveryStatus}`);
      break;
  }

  // Update delivery metadata
  order.delCharge = delCharge;
  if (weight) order.actualWeight = weight;
  order.lastUpdated = statusDate;

  mockOrders.set(waybillId, order);
  console.log(`✅ Order ${order.orderId} successfully synced.\n`);
}

// Start server
app.listen(PORT, () => {
  console.log(`🚀 Koombiyo Webhook Receiver listening on http://localhost:${PORT}`);
  console.log(`📡 Webhook URL for local testing: http://localhost:${PORT}/api/webhooks/koombiyo`);
});
