<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrderVolumeChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected static ?string $heading = 'Orders over the last 7 days';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $startDate = now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();
        $dailyTotals = Order::query()
            ->selectRaw('DATE(created_at) as order_date, COUNT(*) as total')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('order_date')
            ->pluck('total', 'order_date');
        $days = collect(range(0, 6))
            ->map(fn (int $offset) => $startDate->copy()->addDays($offset));

        return [
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $days->map(fn ($day): int => (int) ($dailyTotals[$day->toDateString()] ?? 0))->all(),
                    'backgroundColor' => '#294f3f',
                    'borderColor' => '#294f3f',
                ],
            ],
            'labels' => $days->map(fn ($day): string => $day->format('M j'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
