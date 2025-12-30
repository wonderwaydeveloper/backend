<?php

namespace App\Filament\Resources\ConversionMetrics\Pages;

use App\Filament\Resources\ConversionMetrics\ConversionMetricResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConversionMetrics extends ListRecords
{
    protected static string $resource = ConversionMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
