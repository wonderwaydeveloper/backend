<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Select::make('plan')
                    ->options(['basic' => 'Basic', 'premium' => 'Premium', 'creator' => 'Creator'])
                    ->required(),
                Select::make('status')
                    ->options(['active' => 'Active', 'cancelled' => 'Cancelled', 'expired' => 'Expired'])
                    ->default('active')
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                DateTimePicker::make('starts_at')
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->required(),
                DateTimePicker::make('expires_at'),
            ]);
    }
}
