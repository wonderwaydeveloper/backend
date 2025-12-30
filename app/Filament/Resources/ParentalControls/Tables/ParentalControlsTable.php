<?php

namespace App\Filament\Resources\ParentalControls\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ParentalControlsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('child_id')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('require_follow_approval')
                    ->boolean(),
                IconColumn::make('restrict_dm')
                    ->boolean(),
                IconColumn::make('content_filter')
                    ->boolean(),
                TextColumn::make('daily_post_limit')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('usage_start_time')
                    ->time()
                    ->sortable(),
                TextColumn::make('usage_end_time')
                    ->time()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
