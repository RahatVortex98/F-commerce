<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $variants = $this->record->productVariants()->get()->keyBy('value');

        foreach ($data['variants'] ?? [] as $index => $variantData) {
            $variant = $variants->get($variantData['value'] ?? '');
            $data['variants'][$index]['sku'] = $variant?->sku;
            $data['variants'][$index]['stock'] = $variant?->stock;
            $data['variants'][$index]['price_override'] = $variant?->price_override;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return ProductResource::prepareVariantInventory($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
