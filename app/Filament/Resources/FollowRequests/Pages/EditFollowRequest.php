<?php

namespace App\Filament\Resources\FollowRequests\Pages;

use App\Filament\Resources\FollowRequests\FollowRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFollowRequest extends EditRecord
{
    protected static string $resource = FollowRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
