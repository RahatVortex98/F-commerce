<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Enums\OrderStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StatusHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistories';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('to')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Changed at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('from')
                    ->label('From')
                    ->formatStateUsing(fn (?string $state): string => $state === null
                        ? 'Initial'
                        : OrderStatus::from($state)->label()),
                Tables\Columns\TextColumn::make('to')
                    ->label('To')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => OrderStatus::from($state)->label()),
                Tables\Columns\TextColumn::make('source')
                    ->badge(),
                Tables\Columns\TextColumn::make('actor.name')
                    ->label('Changed by')
                    ->placeholder('System'),
                Tables\Columns\TextColumn::make('note')
                    ->wrap(),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
