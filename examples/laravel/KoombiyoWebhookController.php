<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessKoombiyoWebhookJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class KoombiyoWebhookController extends Controller
{
    /**
     * Handle incoming Koombiyo webhook notifications.
     */
    public function handle(Request $request): JsonResponse
    {
        Log::channel('daily')->info('Koombiyo Webhook Received:', $request->all());

        // 1. Validate payload structure
        $validator = Validator::make($request->all(), [
            'waybill_id'  => 'required|string',
            'status_id'   => 'required',
            'status'      => 'required|string',
            'status_date' => 'required|date_format:Y-m-d H:i:s',
            'del_charge'  => 'nullable',
            'weight'      => 'nullable',
            'reason'      => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::warning('Koombiyo Webhook Validation Failed', [
                'errors'  => $validator->errors()->toArray(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid payload format',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $payload = $validator->validated();

        // 2. Dispatch to background queue for non-blocking execution
        ProcessKoombiyoWebhookJob::dispatch($payload);

        // 3. Immediately respond with HTTP 200 OK (< 100ms)
        return response()->json([
            'status'  => 'success',
            'message' => 'Webhook received successfully',
        ], 200);
    }
}
