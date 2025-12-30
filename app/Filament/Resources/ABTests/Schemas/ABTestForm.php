<?php

namespace App\Filament\Resources\ABTests\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ABTestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                    
                Textarea::make('description')
                    ->rows(3),
                    
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active', 
                        'paused' => 'Paused',
                        'completed' => 'Completed'
                    ])
                    ->default('draft')
                    ->required(),
                    
                TextInput::make('traffic_percentage')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->default(50)
                    ->suffix('%')
                    ->required(),
                    
                KeyValue::make('variants')
                    ->keyLabel('Variant')
                    ->valueLabel('Configuration')
                    ->default(['A' => [], 'B' => []])
                    ->required(),
                    
                KeyValue::make('targeting_rules')
                    ->keyLabel('Rule')
                    ->valueLabel('Value'),
                    
                DateTimePicker::make('starts_at')
                    ->label('Start Date'),
                    
                DateTimePicker::make('ends_at')
                    ->label('End Date'),
            ]);
    }
}
