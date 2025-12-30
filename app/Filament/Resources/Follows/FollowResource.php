<?php

namespace App\Filament\Resources\Follows;

use App\Filament\Resources\Follows\Pages\CreateFollow;
use App\Filament\Resources\Follows\Pages\EditFollow;
use App\Filament\Resources\Follows\Pages\ListFollows;
use App\Filament\Resources\Follows\Schemas\FollowForm;
use App\Filament\Resources\Follows\Tables\FollowsTable;
use App\Models\Follow;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FollowResource extends Resource
{
    protected static ?string $model = Follow::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return FollowForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FollowsTable::configure($table);
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
            'index' => ListFollows::route('/'),
            'create' => CreateFollow::route('/create'),
            'edit' => EditFollow::route('/{record}/edit'),
        ];
    }
}
