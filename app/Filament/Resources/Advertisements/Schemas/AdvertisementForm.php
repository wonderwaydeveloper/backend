<?php

namespace App\Filament\Resources\Advertisements\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AdvertisementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('advertiser_id')
                    ->required()
                    ->numeric(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('media_url')
                    ->url(),
                TextInput::make('target_audience'),
                TextInput::make('budget')
                    ->required()
                    ->numeric(),
                TextInput::make('cost_per_click')
                    ->required()
                    ->numeric()
                    ->default(0.1),
                TextInput::make('cost_per_impression')
                    ->required()
                    ->numeric()
                    ->default(0.01),
                DateTimePicker::make('start_date')
                    ->required(),
                DateTimePicker::make('end_date')
                    ->required(),
                Select::make('status')
                    ->options([
            'pending' => 'Pending',
            'active' => 'Active',
            'paused' => 'Paused',
            'completed' => 'Completed',
            'rejected' => 'Rejected',
        ])
                    ->default('pending')
                    ->required(),
                TextInput::make('impressions_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('clicks_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('conversions_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_spent')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('targeting_criteria'),
            ]);
    }
}
