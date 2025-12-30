<?php

namespace App\Filament\Resources\Communities\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CommunityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('avatar'),
                TextInput::make('banner'),
                Select::make('privacy')
                    ->options(['public' => 'Public', 'private' => 'Private', 'restricted' => 'Restricted'])
                    ->default('public')
                    ->required(),
                TextInput::make('rules'),
                TextInput::make('settings'),
                TextInput::make('created_by')
                    ->required()
                    ->numeric(),
                TextInput::make('member_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('post_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_verified')
                    ->required(),
            ]);
    }
}
