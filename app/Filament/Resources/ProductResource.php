<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name'),
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\Select::make('product_kind')
                    ->label('Product type')
                    ->options([
                        'watch' => 'Watch',
                        'shirt' => 'Shirt',
                        'pant' => 'Pant',
                        'other' => 'Other / no size or color options',
                    ])
                    ->default('other')
                    ->required()
                    ->live()
                    ->helperText('This controls the option name and the measurement chart shown to customers.'),
                Forms\Components\TextInput::make('stock')
                    ->label('Total product quantity available')
                    ->helperText('One stock count shared across every size or color for this product.')
                    ->integer()
                    ->minValue(0)
                    ->required(fn (Forms\Get $get): bool => in_array($get('product_kind'), ['watch', 'shirt', 'pant'], true)),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('৳'),
                Forms\Components\Section::make('Discount (optional)')
                    ->description('Set a sale price and optional end date. Active discounts appear in the storefront offers section.')
                    ->schema([
                        Forms\Components\TextInput::make('sale_price')->numeric()->prefix('৳')->lt('price'),
                        Forms\Components\DateTimePicker::make('sale_ends_at')->label('Offer ends at'),
                    ])->columns(2),
                Forms\Components\FileUpload::make('image')
                    ->label('Main product image')
                    ->image()
                    ->disk('public')
                    ->directory('products')
                    ->maxSize(5120)
                    ->helperText('This is the first image customers see.'),
                Forms\Components\Repeater::make('gallery')
                    ->label('Other product angles')
                    ->schema([
                        Forms\Components\FileUpload::make('image')
                            ->label('Product view')
                            ->required()
                            ->image()
                            ->disk('public')
                            ->directory('products/gallery')
                            ->maxSize(5120),
                    ])
                    ->defaultItems(0)
                    ->maxItems(3)
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => isset($state['image']) ? 'Product angle' : 'New product angle')
                    ->helperText('Add up to three more photos of this same product from different angles.')
                    ->columnSpanFull(),
                Forms\Components\Repeater::make('variants')
                    ->label('Available sizes or colors')
                    ->schema([
                        Forms\Components\Select::make('value')
                            ->label('Color or size')
                            ->options(fn (Forms\Get $get): array => match ($get('../../product_kind')) {
                                'watch' => ['Black' => 'Black', 'Red' => 'Red', 'Blue' => 'Blue', 'Silver' => 'Silver', 'Gold' => 'Gold', 'Rose Gold' => 'Rose Gold', 'Green' => 'Green'],
                                'shirt' => ['S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL', 'XXL' => 'XXL'],
                                'pant' => array_combine(['28', '30', '32', '34', '36', '38', '40', '42', '44'], ['28', '30', '32', '34', '36', '38', '40', '42', '44']),
                                default => [],
                            })
                            ->native(false)
                            ->searchable()
                            ->required()
                            ->distinct(),
                        Forms\Components\TextInput::make('chest_cm')->label('Chest (cm)')->numeric()->minValue(0)->visible(fn (Forms\Get $get): bool => $get('../../product_kind') === 'shirt'),
                        Forms\Components\TextInput::make('body_length_cm')->label('Shirt length (cm)')->numeric()->minValue(0)->visible(fn (Forms\Get $get): bool => $get('../../product_kind') === 'shirt'),
                        Forms\Components\TextInput::make('shoulder_cm')->label('Shoulder (cm)')->numeric()->minValue(0)->visible(fn (Forms\Get $get): bool => $get('../../product_kind') === 'shirt'),
                        Forms\Components\TextInput::make('sleeve_cm')->label('Sleeve (cm)')->numeric()->minValue(0)->visible(fn (Forms\Get $get): bool => $get('../../product_kind') === 'shirt'),
                        Forms\Components\TextInput::make('waist_cm')->label('Waist (cm)')->numeric()->minValue(0)->visible(fn (Forms\Get $get): bool => $get('../../product_kind') === 'pant'),
                        Forms\Components\TextInput::make('hip_cm')->label('Hip (cm)')->numeric()->minValue(0)->visible(fn (Forms\Get $get): bool => $get('../../product_kind') === 'pant'),
                        Forms\Components\TextInput::make('outseam_cm')->label('Pant length (cm)')->numeric()->minValue(0)->visible(fn (Forms\Get $get): bool => $get('../../product_kind') === 'pant'),
                        Forms\Components\TextInput::make('inseam_cm')->label('Inseam (cm)')->numeric()->minValue(0)->visible(fn (Forms\Get $get): bool => $get('../../product_kind') === 'pant'),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->required(fn (Forms\Get $get): bool => in_array($get('product_kind'), ['watch', 'shirt', 'pant'], true))
                    ->addActionLabel('Add size or color')
                    ->reorderable()
                    ->columnSpanFull()
                    ->helperText('Choose the sizes or colors this product comes in. The quantity above is shared by all of these choices.'),
                Forms\Components\Toggle::make('active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')->label('Total stock')->placeholder('Not tracked')->sortable(),
                Tables\Columns\TextColumn::make('sale_price')->label('Sale price')->money('BDT')->placeholder('—'),
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\IconColumn::make('active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
