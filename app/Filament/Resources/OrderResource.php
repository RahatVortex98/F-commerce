<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Jobs\CreatePathaoShipment;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('order_number')
                    ->required(),
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->default(1),
                Forms\Components\TextInput::make('variant_value')->label('Chosen color / size')->disabled()->dehydrated(),
                Forms\Components\TextInput::make('customer_name')
                    ->required(),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->required(),
                Forms\Components\Textarea::make('address')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('total')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('status')
                    ->required()
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'returned' => 'Returned',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('pending'),
                Forms\Components\TextInput::make('courier_name')
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\TextInput::make('courier_tracking_id')
                    ->disabled()
                    ->dehydrated(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('variant_value')->label('Chosen color / size')->placeholder('—'),
                Tables\Columns\TextColumn::make('customer_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Customer email')
                    ->searchable()
                    ->placeholder('Legacy guest order'),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\IconColumn::make('phone_verified_at')
                    ->label('Phone verified')
                    ->boolean(),
                Tables\Columns\IconColumn::make('customer_received_at')
                    ->label('Customer confirmed receipt')
                    ->boolean(),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'confirmed',
                        'primary' => 'shipped',
                        'success' => 'delivered',
                        'danger' => 'returned',
                        'gray' => 'cancelled',
                    ])
                    ->searchable(),
                Tables\Columns\TextColumn::make('courier_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('courier_tracking_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('courier_booking_status')
                    ->label('Courier booking')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'booked' => 'success',
                        'queued', 'booking' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('owner_confirmed_at')
                    ->label('Owner decision')
                    ->formatStateUsing(fn (Order $record): string => match (true) {
                        $record->owner_rejected_at !== null => 'Rejected',
                        $record->owner_confirmed_at !== null => 'Confirmed',
                        $record->courier_booking_status === 'awaiting_owner_confirmation' => 'Awaiting confirmation',
                        $record->phone_verified_at !== null => 'Verified',
                        default => 'Awaiting phone verification',
                    })
                    ->badge()
                    ->color(fn (Order $record): string => match (true) {
                        $record->owner_rejected_at !== null => 'danger',
                        $record->owner_confirmed_at !== null => 'success',
                        $record->courier_booking_status === 'awaiting_owner_confirmation' => 'warning',
                        default => 'gray',
                    }),
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
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'returned' => 'Returned',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Action::make('confirmOrder')
                    ->label('Confirm order')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->phone_verified_at !== null
                        && $record->courier_booking_status === 'awaiting_owner_confirmation'
                        && $record->owner_confirmed_at === null
                        && $record->owner_rejected_at === null)
                    ->requiresConfirmation()
                    ->modalDescription(fn (): string => config('courierhub.couriers.pathao.enabled')
                        ? 'Confirm this verified order and create its Pathao shipment.'
                        : 'Confirm this verified order so the shop can arrange delivery.')
                    ->action(function (Order $record): void {
                        $shouldBookCourier = false;

                        DB::transaction(function () use ($record, &$shouldBookCourier): void {
                            $order = Order::query()->lockForUpdate()->findOrFail($record->id);
                            if (! $order->phone_verified_at || $order->owner_confirmed_at || $order->owner_rejected_at) {
                                return;
                            }

                            $shouldBookCourier = config('courierhub.couriers.pathao.enabled');
                            $order->forceFill([
                                'owner_confirmed_at' => now(),
                                'status' => 'confirmed',
                                'courier_booking_status' => $shouldBookCourier ? 'queued' : 'not_configured',
                                'courier_booking_error' => null,
                            ])->save();
                        });

                        if ($shouldBookCourier) {
                            CreatePathaoShipment::dispatch($record->id)->afterCommit();
                        }

                        Notification::make()
                            ->title($shouldBookCourier ? 'Order confirmed; Pathao booking queued' : 'Order confirmed')
                            ->success()
                            ->send();
                    }),
                Action::make('rejectOrder')
                    ->label('Reject order')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Order $record): bool => $record->phone_verified_at !== null
                        && $record->courier_booking_status === 'awaiting_owner_confirmation'
                        && $record->owner_confirmed_at === null
                        && $record->owner_rejected_at === null)
                    ->requiresConfirmation()
                    ->modalDescription('Reject this order and return its quantity to product stock.')
                    ->action(function (Order $record): void {
                        DB::transaction(function () use ($record): void {
                            $order = Order::query()->lockForUpdate()->findOrFail($record->id);
                            if (! $order->phone_verified_at || $order->owner_confirmed_at || $order->owner_rejected_at) {
                                return;
                            }

                            if ($order->variant_stock_reduced) {
                                $product = $order->product()->lockForUpdate()->firstOrFail();
                                if ($product->stock !== null) {
                                    $product->increment('stock', $order->quantity);
                                }
                            }

                            $order->forceFill([
                                'owner_rejected_at' => now(),
                                'variant_stock_reduced' => false,
                                'status' => 'cancelled',
                                'courier_booking_status' => 'rejected_by_owner',
                            ])->save();
                        });

                        Notification::make()
                            ->title('Order rejected and stock returned')
                            ->success()
                            ->send();
                    }),
                Action::make('bookCourier')
                    ->label('Retry Pathao booking')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->phone_verified_at !== null
                        && $record->courier_booking_status === 'failed'
                        && config('courierhub.couriers.pathao.enabled'))
                    ->requiresConfirmation()
                    ->modalDescription('Check the Pathao merchant portal first. If a previous request was accepted but its response was lost, retrying could create a duplicate parcel.')
                    ->action(function (Order $record): void {
                        $record->update([
                            'owner_confirmed_at' => $record->owner_confirmed_at ?? now(),
                            'courier_booking_status' => 'queued',
                            'courier_booking_error' => null,
                        ]);
                        CreatePathaoShipment::dispatch($record->id);

                        Notification::make()
                            ->title('Pathao retry queued')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
