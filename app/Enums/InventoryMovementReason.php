<?php

namespace App\Enums;

enum InventoryMovementReason: string
{
    case Sale = 'sale';
    case Cancel = 'cancel';
    case Return = 'return';
    case Restock = 'restock';
    case Adjustment = 'adjustment';
    case Reject = 'reject';
    case Expired = 'expired';

}
