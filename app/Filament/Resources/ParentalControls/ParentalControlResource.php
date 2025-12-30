<?php

namespace App\Filament\Resources\ParentalControls;

use App\Filament\Resources\ParentalControls\Pages\CreateParentalControl;
use App\Filament\Resources\ParentalControls\Pages\EditParentalControl;
use App\Filament\Resources\ParentalControls\Pages\ListParentalControls;
use App\Filament\Resources\ParentalControls\Pages\ViewParentalControl;
use App\Filament\Resources\ParentalControls\Schemas\ParentalControlForm;
use App\Filament\Resources\ParentalControls\Schemas\ParentalControlInfolist;
use App\Filament\Resources\ParentalControls\Tables\ParentalControlsTable;
use App\Models\ParentalControl;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ParentalControlResource extends Resource
{
    protected static ?string $model = ParentalControl::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ParentalControlForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ParentalControlInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ParentalControlsTable::configure($table);
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
            'index' => ListParentalControls::route('/'),
            'create' => CreateParentalControl::route('/create'),
            'view' => ViewParentalControl::route('/{record}'),
            'edit' => EditParentalControl::route('/{record}/edit'),
        ];
    }
}
