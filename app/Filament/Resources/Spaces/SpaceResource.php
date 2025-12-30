<?php

namespace App\Filament\Resources\Spaces;

use App\Filament\Resources\Spaces\Pages\CreateSpace;
use App\Filament\Resources\Spaces\Pages\EditSpace;
use App\Filament\Resources\Spaces\Pages\ListSpaces;
use App\Filament\Resources\Spaces\Pages\ViewSpace;
use App\Filament\Resources\Spaces\Schemas\SpaceForm;
use App\Filament\Resources\Spaces\Schemas\SpaceInfolist;
use App\Filament\Resources\Spaces\Tables\SpacesTable;
use App\Models\Space;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SpaceResource extends Resource
{
    protected static ?string $model = Space::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return SpaceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SpaceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SpacesTable::configure($table);
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
            'index' => ListSpaces::route('/'),
            'create' => CreateSpace::route('/create'),
            'view' => ViewSpace::route('/{record}'),
            'edit' => EditSpace::route('/{record}/edit'),
        ];
    }
}
