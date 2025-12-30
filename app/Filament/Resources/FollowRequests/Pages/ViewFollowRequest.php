<?php

namespace App\Filament\Resources\FollowRequests\Pages;

use App\Filament\Resources\FollowRequests\FollowRequestResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFollowRequest extends ViewRecord
{
    protected static string $resource = FollowRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
