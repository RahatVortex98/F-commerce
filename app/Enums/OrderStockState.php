<?php

namespace App\Enums;

enum OrderStockState: string
{
    case None = 'none';
    case Reserved = 'reserved';
    case Released = 'released';

}
