<?php

namespace App\Filament\Resources\UserLists;

use App\Filament\Resources\UserLists\Pages\CreateUserList;
use App\Filament\Resources\UserLists\Pages\EditUserList;
use App\Filament\Resources\UserLists\Pages\ListUserLists;
use App\Filament\Resources\UserLists\Pages\ViewUserList;
use App\Filament\Resources\UserLists\Schemas\UserListForm;
use App\Filament\Resources\UserLists\Schemas\UserListInfolist;
use App\Filament\Resources\UserLists\Tables\UserListsTable;
use App\Models\UserList;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserListResource extends Resource
{
    protected static ?string $model = UserList::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return UserListForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserListInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserListsTable::configure($table);
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
            'index' => ListUserLists::route('/'),
            'create' => CreateUserList::route('/create'),
            'view' => ViewUserList::route('/{record}'),
            'edit' => EditUserList::route('/{record}/edit'),
        ];
    }
}
