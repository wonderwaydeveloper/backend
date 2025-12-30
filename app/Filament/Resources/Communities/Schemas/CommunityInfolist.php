<?php

namespace App\Filament\Resources\Communities\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CommunityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('description')
                    ->columnSpanFull(),
                TextEntry::make('slug'),
                TextEntry::make('avatar')
                    ->placeholder('-'),
                TextEntry::make('banner')
                    ->placeholder('-'),
                TextEntry::make('privacy')
                    ->badge(),
                TextEntry::make('created_by')
                    ->numeric(),
                TextEntry::make('member_count')
                    ->numeric(),
                TextEntry::make('post_count')
                    ->numeric(),
                IconEntry::make('is_verified')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
