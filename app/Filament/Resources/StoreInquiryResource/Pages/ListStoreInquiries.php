<?php

namespace App\Filament\Resources\StoreInquiryResource\Pages;

use App\Filament\Resources\StoreInquiryResource;
use Filament\Resources\Pages\ListRecords;

class ListStoreInquiries extends ListRecords
{
    protected static string $resource = StoreInquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
