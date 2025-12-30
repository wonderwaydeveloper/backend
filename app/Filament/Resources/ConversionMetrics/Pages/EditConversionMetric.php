<?php

namespace App\Filament\Resources\ConversionMetrics\Pages;

use App\Filament\Resources\ConversionMetrics\ConversionMetricResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditConversionMetric extends EditRecord
{
    protected static string $resource = ConversionMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
