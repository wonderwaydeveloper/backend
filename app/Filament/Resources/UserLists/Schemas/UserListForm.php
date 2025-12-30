<?php

namespace App\Filament\Resources\UserLists\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class UserListForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('privacy')
                    ->options(['public' => 'Public', 'private' => 'Private'])
                    ->default('public')
                    ->required(),
                TextInput::make('members_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('subscribers_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                FileUpload::make('banner_image')
                    ->image(),
            ]);
    }
}
