<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('username')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('subscription_plan')
                    ->searchable(),
                IconColumn::make('is_premium')
                    ->boolean(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('phone_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('date_of_birth')
                    ->date()
                    ->sortable(),
                TextColumn::make('avatar')
                    ->searchable(),
                TextColumn::make('cover')
                    ->searchable(),
                IconColumn::make('is_online')
                    ->boolean(),
                TextColumn::make('last_seen_at')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_private')
                    ->boolean(),
                IconColumn::make('is_child')
                    ->boolean(),
                IconColumn::make('two_factor_enabled')
                    ->boolean(),
                TextColumn::make('two_factor_secret')
                    ->searchable(),
                TextColumn::make('followers_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('following_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('posts_count')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('email_notifications_enabled')
                    ->boolean(),
                TextColumn::make('google_id')
                    ->searchable(),
                TextColumn::make('github_id')
                    ->searchable(),
                TextColumn::make('facebook_id')
                    ->searchable(),
                TextColumn::make('last_active_at')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_flagged')
                    ->boolean(),
                IconColumn::make('is_suspended')
                    ->boolean(),
                IconColumn::make('is_banned')
                    ->boolean(),
                TextColumn::make('suspended_until')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('banned_at')
                    ->dateTime()
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
