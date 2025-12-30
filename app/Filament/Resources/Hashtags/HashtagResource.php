<?php

namespace App\Filament\Resources\Hashtags;

use App\Filament\Resources\Hashtags\Pages\CreateHashtag;
use App\Filament\Resources\Hashtags\Pages\EditHashtag;
use App\Filament\Resources\Hashtags\Pages\ListHashtags;
use App\Filament\Resources\Hashtags\Schemas\HashtagForm;
use App\Filament\Resources\Hashtags\Tables\HashtagsTable;
use App\Models\Hashtag;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HashtagResource extends Resource
{
    protected static ?string $model = Hashtag::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return HashtagForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HashtagsTable::configure($table);
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
            'index' => ListHashtags::route('/'),
            'create' => CreateHashtag::route('/create'),
            'edit' => EditHashtag::route('/{record}/edit'),
        ];
    }
}
