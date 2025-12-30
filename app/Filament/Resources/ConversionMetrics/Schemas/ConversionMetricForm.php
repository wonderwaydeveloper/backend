<?php

namespace App\Filament\Resources\ConversionMetrics\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ConversionMetricForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->numeric(),
                TextInput::make('event_type')
                    ->required(),
                TextInput::make('event_data'),
                TextInput::make('conversion_type')
                    ->required(),
                TextInput::make('conversion_value')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('source'),
                TextInput::make('campaign'),
                TextInput::make('session_id'),
                TextInput::make('ip_address'),
                Textarea::make('user_agent')
                    ->columnSpanFull(),
            ]);
    }
}
