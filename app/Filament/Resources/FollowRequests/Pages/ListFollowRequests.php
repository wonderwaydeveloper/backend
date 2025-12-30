<?php

namespace App\Filament\Resources\FollowRequests\Pages;

use App\Filament\Resources\FollowRequests\FollowRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFollowRequests extends ListRecords
{
    protected static string $resource = FollowRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
