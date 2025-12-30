<?php

namespace App\Filament\Resources\ParentalControls\Pages;

use App\Filament\Resources\ParentalControls\ParentalControlResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditParentalControl extends EditRecord
{
    protected static string $resource = ParentalControlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
