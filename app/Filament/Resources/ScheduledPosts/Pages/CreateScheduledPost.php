<?php

namespace App\Filament\Resources\ScheduledPosts\Pages;

use App\Filament\Resources\ScheduledPosts\ScheduledPostResource;
use Filament\Resources\Pages\CreateRecord;

class CreateScheduledPost extends CreateRecord
{
    protected static string $resource = ScheduledPostResource::class;
}
