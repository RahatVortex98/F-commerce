<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrderStatusChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected static ?string $heading = 'Orders by status';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $statuses = collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $status): array => [$status->value => $status->label()]);
        $counts = Order::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $statuses->keys()->map(fn (string $status): int => (int) ($counts[$status] ?? 0))->all(),
                    'backgroundColor' => ['#d6a64f', '#729378', '#6788a1', '#456e50', '#bd795e', '#92948b'],
                ],
            ],
            'labels' => $statuses->values()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
