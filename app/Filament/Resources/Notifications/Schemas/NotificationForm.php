<?php

namespace App\Filament\Resources\Notifications\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class NotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('from_user_id')
                    ->numeric(),
                Select::make('type')
                    ->options([
            'like' => 'Like',
            'comment' => 'Comment',
            'follow' => 'Follow',
            'mention' => 'Mention',
            'repost' => 'Repost',
            'quote' => 'Quote',
        ])
                    ->required(),
                TextInput::make('notifiable_type')
                    ->required(),
                TextInput::make('notifiable_id')
                    ->required()
                    ->numeric(),
                Textarea::make('data')
                    ->columnSpanFull(),
                DateTimePicker::make('read_at'),
            ]);
    }
}
