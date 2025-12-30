<?php

namespace App\Filament\Resources\ABTests\Pages;

use App\Filament\Resources\ABTests\ABTestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListABTests extends ListRecords
{
    protected static string $resource = ABTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
