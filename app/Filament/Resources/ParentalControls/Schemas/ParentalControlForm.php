<?php

namespace App\Filament\Resources\ParentalControls\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ParentalControlForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('child_id')
                    ->required()
                    ->numeric(),
                Toggle::make('require_follow_approval')
                    ->required(),
                Toggle::make('restrict_dm')
                    ->required(),
                Toggle::make('content_filter')
                    ->required(),
                TextInput::make('daily_post_limit')
                    ->required()
                    ->numeric()
                    ->default(10),
                TimePicker::make('usage_start_time'),
                TimePicker::make('usage_end_time'),
            ]);
    }
}
