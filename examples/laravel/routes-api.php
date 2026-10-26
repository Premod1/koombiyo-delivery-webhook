<?php

use App\Http\Controllers\Webhooks\KoombiyoWebhookController;
use Illuminate\Support\Facades\Route;

/**
 * Koombiyo Webhook Route
 * Add this line inside your `routes/api.php` file.
 * 
 * Note: `routes/api.php` automatically bypasses CSRF token checks.
 */
Route::post('/webhooks/koombiyo', [KoombiyoWebhookController::class, 'handle'])
    ->name('webhooks.koombiyo');
