<?php

namespace App\Providers;

use App\Contracts\SmsGateway;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Services\ConfiguredSmsGateway;
use CourierHub\Enums\CourierStatus;
use CourierHub\Events\CourierWebhookReceived;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmsGateway::class, ConfiguredSmsGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('customer-login', function (Request $request): Limit {
            $email = $request->input('email');
            $email = is_string($email) ? Str::lower($email) : '';
            $key = hash('sha256', $email.'|'.$request->ip());

            return Limit::perMinute(6)->by($key);
        });

        Event::listen(CourierWebhookReceived::class, function (CourierWebhookReceived $event): void {
            $webhook = $event->webhook;
            $order = Order::query()
                ->where('courier_tracking_id', $webhook->tracking_id)
                ->when($webhook->merchant_order_id, fn ($query) => $query->orWhere('order_number', $webhook->merchant_order_id))
                ->first();

            if (! $order) {
                return;
            }

            $status = match ($webhook->status) {
                CourierStatus::Pending => 'pending',
                CourierStatus::Confirmed => 'confirmed',
                CourierStatus::PickedUp, CourierStatus::InTransit, CourierStatus::OutForDelivery => 'shipped',
                CourierStatus::Delivered, CourierStatus::PartialDelivered => 'delivered',
                CourierStatus::Cancelled => 'cancelled',
                CourierStatus::ReturnInTransit, CourierStatus::Returned, CourierStatus::Failed => 'returned',
                default => null,
            };

            if ($status) {
                $order->update(['status' => $status]);
            }
        });

        View::composer(['layouts.shop', 'shop', 'product', 'checkout', 'trust', 'verify-phone', 'order-success'], function ($view): void {
            $storeSettings = StoreSetting::query()->find(1);
            $announcements = $storeSettings?->announcements
                ? array_values(array_filter(array_map('trim', explode('|', $storeSettings->announcements))))
                : config('storefront.announcements', []);

            $view->with('storeSettings', $storeSettings);
            $view->with('storeAnnouncements', $announcements);
            $view->with('storeServiceHighlights', $storeSettings?->service_highlights ?: config('storefront.services', []));
            $view->with('storeProducts', Product::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name']));
            $view->with('storeCategories', Category::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name']));
        });
    }
}
