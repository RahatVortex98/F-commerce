<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreInquiryResource\Pages;
use App\Models\StoreInquiry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StoreInquiryResource extends Resource
{
    protected static ?string $model = StoreInquiry::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox';

    protected static ?string $navigationLabel = 'Customer messages';

    protected static ?string $modelLabel = 'customer message';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('customer_name')->disabled(),
            TextInput::make('email')->disabled(),
            TextInput::make('phone')->disabled(),
            Select::make('inquiry_type')
                ->options([
                    'general' => 'General question',
                    'complaint' => 'Complaint',
                    'suggestion' => 'Suggestion',
                    'product_request' => 'Product request',
                ])->disabled(),
            TextInput::make('product_name')->disabled()->columnSpanFull(),
            Textarea::make('message')->disabled()->rows(6)->columnSpanFull(),
            Select::make('status')->required()->options([
                'new' => 'New',
                'contacted' => 'Contacted',
                'resolved' => 'Resolved',
            ]),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('customer_name')->searchable()->sortable(),
                TextColumn::make('inquiry_type')->label('Type')->badge()->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),
                TextColumn::make('product_name')->label('Product requested')->searchable()->placeholder('—'),
                TextColumn::make('email')->searchable()->placeholder('—'),
                TextColumn::make('phone')->searchable()->placeholder('—'),
                SelectColumn::make('status')->options([
                    'new' => 'New',
                    'contacted' => 'Contacted',
                    'resolved' => 'Resolved',
                ]),
                TextColumn::make('created_at')->label('Received')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('inquiry_type')->options([
                    'general' => 'General question',
                    'complaint' => 'Complaint',
                    'suggestion' => 'Suggestion',
                    'product_request' => 'Product request',
                ]),
                SelectFilter::make('status')->options([
                    'new' => 'New',
                    'contacted' => 'Contacted',
                    'resolved' => 'Resolved',
                ]),
            ])
            ->actions([
                EditAction::make()->label('Read / update'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStoreInquiries::route('/'),
            'edit' => Pages\EditStoreInquiry::route('/{record}/edit'),
        ];
    }
}
