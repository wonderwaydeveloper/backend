<?php

namespace App\Filament\Resources\ABTests\Pages;

use App\Filament\Resources\ABTests\ABTestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditABTest extends EditRecord
{
    protected static string $resource = ABTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
