<?php

namespace App\Filament\Resources\Spaces\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SpaceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('host_id')
                    ->required()
                    ->numeric(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(['scheduled' => 'Scheduled', 'live' => 'Live', 'ended' => 'Ended'])
                    ->default('scheduled')
                    ->required(),
                Select::make('privacy')
                    ->options(['public' => 'Public', 'followers' => 'Followers', 'invited' => 'Invited'])
                    ->default('public')
                    ->required(),
                TextInput::make('max_participants')
                    ->required()
                    ->numeric()
                    ->default(10),
                TextInput::make('current_participants')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('scheduled_at'),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('ended_at'),
                TextInput::make('settings'),
            ]);
    }
}
