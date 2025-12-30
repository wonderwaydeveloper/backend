<?php

namespace App\Filament\Resources\ParentalControls\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ParentalControlInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('child_id')
                    ->numeric(),
                IconEntry::make('require_follow_approval')
                    ->boolean(),
                IconEntry::make('restrict_dm')
                    ->boolean(),
                IconEntry::make('content_filter')
                    ->boolean(),
                TextEntry::make('daily_post_limit')
                    ->numeric(),
                TextEntry::make('usage_start_time')
                    ->time()
                    ->placeholder('-'),
                TextEntry::make('usage_end_time')
                    ->time()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
