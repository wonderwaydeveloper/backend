<?php

namespace App\Filament\Resources\Follows\Pages;

use App\Filament\Resources\Follows\FollowResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFollows extends ListRecords
{
    protected static string $resource = FollowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
