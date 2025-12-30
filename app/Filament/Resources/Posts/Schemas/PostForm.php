<?php

namespace App\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PostForm
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
                TextInput::make('video'),
                TextInput::make('gif_url')
                    ->url(),
                Toggle::make('is_draft')
                    ->required(),
                Toggle::make('is_flagged')
                    ->required(),
                Toggle::make('is_hidden')
                    ->required(),
                Toggle::make('is_deleted')
                    ->required(),
                DateTimePicker::make('flagged_at'),
                Toggle::make('is_thread')
                    ->required(),
                TextInput::make('reply_settings')
                    ->required()
                    ->default('everyone'),
                TextInput::make('likes_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('comments_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('reposts_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('views_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('quoted_post_id')
                    ->numeric(),
                TextInput::make('thread_id')
                    ->numeric(),
                TextInput::make('thread_position')
                    ->numeric(),
                TextInput::make('community_id')
                    ->numeric(),
                Toggle::make('is_pinned')
                    ->required(),
                DateTimePicker::make('published_at'),
                DateTimePicker::make('last_edited_at'),
                Toggle::make('is_edited')
                    ->required(),
            ]);
    }
}
