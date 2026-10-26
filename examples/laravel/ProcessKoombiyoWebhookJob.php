<?php

namespace App\Jobs;

use App\Models\Delivery;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessKoombiyoWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        $waybillId  = $this->data['waybill_id'];
        $statusId   = (int)$this->data['status_id'];
        $statusName = $this->data['status'];
        $statusDate = $this->data['status_date'];
        $delCharge  = isset($this->data['del_charge']) ? (float)$this->data['del_charge'] : 0.0;
        $weight     = isset($this->data['weight']) ? (float)$this->data['weight'] : null;
        $reason     = $this->data['reason'] ?? null;

        DB::transaction(function () use ($waybillId, $statusId, $statusName, $statusDate, $delCharge, $weight, $reason) {
            // Find shipment by waybill ID
            $delivery = Delivery::where('waybill_id', $waybillId)->first();

            if (!$delivery) {
                Log::warning("Koombiyo Webhook: Delivery with waybill #{$waybillId} not found.");
                return;
            }

            // Guard against out-of-order events
            if ($delivery->last_status_date && $delivery->last_status_date > $statusDate) {
                Log::info("Koombiyo Webhook: Ignored older status event for waybill #{$waybillId}.");
                return;
            }

            // Update delivery record
            $delivery->update([
                'status_id'        => $statusId,
                'status_name'      => $statusName,
                'last_status_date' => $statusDate,
                'delivery_charge'  => $delCharge,
                'actual_weight'    => $weight ?? $delivery->actual_weight,
                'status_reason'    => $reason,
            ]);

            // Sync with parent Order
            $order = $delivery->order;
            if ($order) {
                $this->mapOrderStatus($order, $statusId, $statusName);
            }

            Log::info("Koombiyo Webhook: Processed waybill #{$waybillId} -> Status: {$statusName}");
        });
    }

    /**
     * Map Koombiyo delivery status to your internal business states.
     */
    protected function mapOrderStatus(Order $order, int $statusId, string $statusName): void
    {
        switch ($statusId) {
            case 5: // Delivered
                $order->update([
                    'delivery_status' => 'delivered',
                    'payment_status'  => 'paid', // Cash on Delivery (COD) collected
                    'delivered_at'    => now(),
                ]);
                // Trigger customer SMS / WhatsApp / Email notification here
                break;

            case 9: // Pending Return
                $order->update([
                    'delivery_status' => 'return_pending',
                ]);
                break;

            case 15: // Return Received at Merchant Store
                $order->update([
                    'delivery_status' => 'returned',
                    'payment_status'  => 'cancelled_or_refunded',
                ]);
                break;

            default:
                $order->update([
                    'delivery_status' => strtolower(str_replace(' ', '_', $statusName)),
                ]);
                break;
        }
    }
}
