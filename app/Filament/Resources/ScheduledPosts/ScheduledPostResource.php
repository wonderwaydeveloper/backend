<?php

namespace App\Filament\Resources\ScheduledPosts;

use App\Filament\Resources\ScheduledPosts\Pages\CreateScheduledPost;
use App\Filament\Resources\ScheduledPosts\Pages\EditScheduledPost;
use App\Filament\Resources\ScheduledPosts\Pages\ListScheduledPosts;
use App\Filament\Resources\ScheduledPosts\Pages\ViewScheduledPost;
use App\Filament\Resources\ScheduledPosts\Schemas\ScheduledPostForm;
use App\Filament\Resources\ScheduledPosts\Schemas\ScheduledPostInfolist;
use App\Filament\Resources\ScheduledPosts\Tables\ScheduledPostsTable;
use App\Models\ScheduledPost;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ScheduledPostResource extends Resource
{
    protected static ?string $model = ScheduledPost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'content';

    public static function form(Schema $schema): Schema
    {
        return ScheduledPostForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ScheduledPostInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScheduledPostsTable::configure($table);
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
            'index' => ListScheduledPosts::route('/'),
            'create' => CreateScheduledPost::route('/create'),
            'view' => ViewScheduledPost::route('/{record}'),
            'edit' => EditScheduledPost::route('/{record}/edit'),
        ];
    }
}
