<?php

namespace App\Filament\Resources\Hashtags\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HashtagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('posts_count')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
