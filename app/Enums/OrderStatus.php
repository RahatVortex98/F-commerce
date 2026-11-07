<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Hold = 'hold';
    case Confirmed = 'confirmed';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Returned = 'returned';

    public static function transitionMap(): array
    {
        return [
            self::Pending->value => [self::Hold, self::Confirmed, self::Cancelled],
            self::Hold->value => [self::Pending, self::Confirmed, self::Cancelled],
            self::Confirmed->value => [self::Shipped, self::Cancelled],
            self::Shipped->value => [self::Delivered, self::Returned],
            self::Delivered->value => [self::Returned],
            self::Cancelled->value => [],
            self::Returned->value => [],
        ];
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, self::transitionMap()[$this->value], true);
    }

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending, self::Hold => 'warning',
            self::Confirmed => 'info',
            self::Shipped => 'primary',
            self::Delivered => 'success',
            self::Cancelled, self::Returned => 'danger',
        };
    }

    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }
}
