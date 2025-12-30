<?php

namespace App\Filament\Resources\ParentalControls\Pages;

use App\Filament\Resources\ParentalControls\ParentalControlResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewParentalControl extends ViewRecord
{
    protected static string $resource = ParentalControlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
