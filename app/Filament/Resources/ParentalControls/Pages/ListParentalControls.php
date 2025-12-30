<?php

namespace App\Filament\Resources\ParentalControls\Pages;

use App\Filament\Resources\ParentalControls\ParentalControlResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListParentalControls extends ListRecords
{
    protected static string $resource = ParentalControlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
