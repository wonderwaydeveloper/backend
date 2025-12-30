<?php

namespace App\Filament\Resources\Posts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                ImageColumn::make('image'),
                TextColumn::make('video')
                    ->searchable(),
                TextColumn::make('gif_url')
                    ->searchable(),
                IconColumn::make('is_draft')
                    ->boolean(),
                IconColumn::make('is_flagged')
                    ->boolean(),
                IconColumn::make('is_hidden')
                    ->boolean(),
                IconColumn::make('is_deleted')
                    ->boolean(),
                TextColumn::make('flagged_at')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_thread')
                    ->boolean(),
                TextColumn::make('reply_settings')
                    ->searchable(),
                TextColumn::make('likes_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('comments_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reposts_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('views_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('quoted_post_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('thread_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('thread_position')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('community_id')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_pinned')
                    ->boolean(),
                TextColumn::make('published_at')
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
                TextColumn::make('last_edited_at')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_edited')
                    ->boolean(),
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
