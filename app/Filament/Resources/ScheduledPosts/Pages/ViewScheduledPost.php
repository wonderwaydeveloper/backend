<?php

namespace App\Filament\Resources\ScheduledPosts\Pages;

use App\Filament\Resources\ScheduledPosts\ScheduledPostResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewScheduledPost extends ViewRecord
{
    protected static string $resource = ScheduledPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
