<?php

namespace App\Filament\Resources\Polls\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PollForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('post_id')
                    ->relationship('post', 'id')
                    ->required(),
                TextInput::make('question')
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->required(),
                TextInput::make('total_votes')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
