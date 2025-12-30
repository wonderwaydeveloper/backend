<?php

namespace App\Filament\Resources\ABTests;

use App\Filament\Resources\ABTests\Pages\CreateABTest;
use App\Filament\Resources\ABTests\Pages\EditABTest;
use App\Filament\Resources\ABTests\Pages\ListABTests;
use App\Filament\Resources\ABTests\Pages\ViewABTest;
use App\Filament\Resources\ABTests\Schemas\ABTestForm;
use App\Filament\Resources\ABTests\Schemas\ABTestInfolist;
use App\Filament\Resources\ABTests\Tables\ABTestsTable;
use App\Models\ABTest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ABTestResource extends Resource
{
    protected static ?string $model = ABTest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ABTestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ABTestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ABTestsTable::configure($table);
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
            'index' => ListABTests::route('/'),
            'create' => CreateABTest::route('/create'),
            'view' => ViewABTest::route('/{record}'),
            'edit' => EditABTest::route('/{record}/edit'),
        ];
    }
}
