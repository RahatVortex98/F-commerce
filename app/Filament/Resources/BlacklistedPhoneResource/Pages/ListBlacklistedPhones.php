<?php

namespace App\Filament\Resources\BlacklistedPhoneResource\Pages;

use App\Filament\Resources\BlacklistedPhoneResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBlacklistedPhones extends ListRecords
{
    protected static string $resource = BlacklistedPhoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
