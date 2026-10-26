# Laravel — Koombiyo Webhook Integration

Enterprise-ready implementation of Koombiyo Delivery Webhooks for Laravel (v10 / v11+).

## Architecture
1. **Controller (`KoombiyoWebhookController.php`)**: Validates the payload and immediately returns an HTTP `200 OK` JSON response.
2. **Queue Job (`ProcessKoombiyoWebhookJob.php`)**: Processes database updates, order status transitions, and customer notifications asynchronously in the background.

## Setup Steps

### 1. Copy Files into your Laravel project
- Copy `KoombiyoWebhookController.php` to `app/Http/Controllers/Webhooks/`
- Copy `ProcessKoombiyoWebhookJob.php` to `app/Jobs/`

### 2. Register Route
In `routes/api.php`:
```php
use App\Http\Controllers\Webhooks\KoombiyoWebhookController;

Route::post('/webhooks/koombiyo', [KoombiyoWebhookController::class, 'handle'])
    ->name('webhooks.koombiyo');
```

> **Note on CSRF**: Since the route is registered in `routes/api.php`, Laravel's CSRF verification middleware is bypassed by default. If you place it in `routes/web.php`, you must exclude it in `bootstrap/app.php` (Laravel 11) or `VerifyCsrfToken` middleware (Laravel 10).

### 3. Run Queue Worker
Ensure your queue worker is running:
```bash
php artisan queue:work
```

### 4. Test Locally
Expose your local development server:
```bash
php artisan serve
ngrok http 8000
```
Then use `https://xxxx.ngrok-free.app/api/webhooks/koombiyo` in the **Koombiyo Client Portal**.
