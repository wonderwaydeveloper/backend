<?php

namespace App\Filament\Resources\Posts\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PostInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user_id')
                    ->numeric(),
                TextEntry::make('content')
                    ->columnSpanFull(),
                ImageEntry::make('image')
                    ->placeholder('-'),
                TextEntry::make('video')
                    ->placeholder('-'),
                TextEntry::make('gif_url')
                    ->placeholder('-'),
                IconEntry::make('is_draft')
                    ->boolean(),
                IconEntry::make('is_flagged')
                    ->boolean(),
                IconEntry::make('is_hidden')
                    ->boolean(),
                IconEntry::make('is_deleted')
                    ->boolean(),
                TextEntry::make('flagged_at')
                    ->dateTime()
                    ->placeholder('-'),
                IconEntry::make('is_thread')
                    ->boolean(),
                TextEntry::make('reply_settings'),
                TextEntry::make('likes_count')
                    ->numeric(),
                TextEntry::make('comments_count')
                    ->numeric(),
                TextEntry::make('reposts_count')
                    ->numeric(),
                TextEntry::make('views_count')
                    ->numeric(),
                TextEntry::make('quoted_post_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('thread_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('thread_position')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('community_id')
                    ->numeric()
                    ->placeholder('-'),
                IconEntry::make('is_pinned')
                    ->boolean(),
                TextEntry::make('published_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('last_edited_at')
                    ->dateTime()
                    ->placeholder('-'),
                IconEntry::make('is_edited')
                    ->boolean(),
            ]);
    }
}
