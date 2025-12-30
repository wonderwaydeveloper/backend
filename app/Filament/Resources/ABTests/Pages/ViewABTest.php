<?php

namespace App\Filament\Resources\ABTests\Pages;

use App\Filament\Resources\ABTests\ABTestResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewABTest extends ViewRecord
{
    protected static string $resource = ABTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
