<?php

namespace App\Filament\Resources\Moments\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MomentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                FileUpload::make('cover_image')
                    ->image(),
                Select::make('privacy')
                    ->options(['public' => 'Public', 'private' => 'Private'])
                    ->default('public')
                    ->required(),
                Toggle::make('is_featured')
                    ->required(),
                TextInput::make('posts_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('views_count')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
