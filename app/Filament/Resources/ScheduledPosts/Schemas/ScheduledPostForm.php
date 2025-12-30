<?php

namespace App\Filament\Resources\ScheduledPosts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ScheduledPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                FileUpload::make('image')
                    ->image(),
                DateTimePicker::make('scheduled_at')
                    ->required(),
                Select::make('status')
                    ->options(['pending' => 'Pending', 'published' => 'Published', 'failed' => 'Failed'])
                    ->default('pending')
                    ->required(),
                TextInput::make('post_id')
                    ->numeric(),
            ]);
    }
}
