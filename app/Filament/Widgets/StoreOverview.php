<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Active products', Product::query()->where('active', true)->count())
                ->description('Available in your storefront')
                ->color('success'),
            Stat::make('Units in stock', number_format((int) Product::query()->where('active', true)->sum('stock')))
                ->description('Across active products')
                ->color('info'),
            Stat::make('Customer accounts', User::query()->where('is_admin', false)->count())
                ->description('Registered shoppers')
                ->color('primary'),
            Stat::make('All orders', Order::query()->count())
                ->description('Including orders in progress')
                ->color('gray'),
            Stat::make('Orders to fulfill', Order::query()->whereIn('status', [
                OrderStatus::Confirmed->value,
                OrderStatus::Shipped->value,
            ])->count())
                ->description('Confirmed or on the way')
                ->color('warning'),
            Stat::make('Delivered sales', '৳'.number_format((float) Order::query()
                ->where('status', OrderStatus::Delivered->value)
                ->sum('total'), 2))
                ->description('Cash-on-delivery orders marked delivered')
                ->color('success'),
        ];
    }
}
