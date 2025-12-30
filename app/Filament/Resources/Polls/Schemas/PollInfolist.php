<?php

namespace App\Filament\Resources\Polls\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PollInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('post.id')
                    ->label('Post'),
                TextEntry::make('question'),
                TextEntry::make('ends_at')
                    ->dateTime(),
                TextEntry::make('total_votes')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
