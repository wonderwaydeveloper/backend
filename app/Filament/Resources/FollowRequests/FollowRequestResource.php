<?php

namespace App\Filament\Resources\FollowRequests;

use App\Filament\Resources\FollowRequests\Pages\CreateFollowRequest;
use App\Filament\Resources\FollowRequests\Pages\EditFollowRequest;
use App\Filament\Resources\FollowRequests\Pages\ListFollowRequests;
use App\Filament\Resources\FollowRequests\Pages\ViewFollowRequest;
use App\Filament\Resources\FollowRequests\Schemas\FollowRequestForm;
use App\Filament\Resources\FollowRequests\Schemas\FollowRequestInfolist;
use App\Filament\Resources\FollowRequests\Tables\FollowRequestsTable;
use App\Models\FollowRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FollowRequestResource extends Resource
{
    protected static ?string $model = FollowRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return FollowRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FollowRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FollowRequestsTable::configure($table);
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
            'index' => ListFollowRequests::route('/'),
            'create' => CreateFollowRequest::route('/create'),
            'view' => ViewFollowRequest::route('/{record}'),
            'edit' => EditFollowRequest::route('/{record}/edit'),
        ];
    }
}
