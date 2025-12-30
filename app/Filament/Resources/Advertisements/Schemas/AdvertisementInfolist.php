<?php

namespace App\Filament\Resources\Advertisements\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AdvertisementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('advertiser_id')
                    ->numeric(),
                TextEntry::make('title'),
                TextEntry::make('content')
                    ->columnSpanFull(),
                TextEntry::make('media_url')
                    ->placeholder('-'),
                TextEntry::make('budget')
                    ->numeric(),
                TextEntry::make('cost_per_click')
                    ->numeric(),
                TextEntry::make('cost_per_impression')
                    ->numeric(),
                TextEntry::make('start_date')
                    ->dateTime(),
                TextEntry::make('end_date')
                    ->dateTime(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('impressions_count')
                    ->numeric(),
                TextEntry::make('clicks_count')
                    ->numeric(),
                TextEntry::make('conversions_count')
                    ->numeric(),
                TextEntry::make('total_spent')
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
