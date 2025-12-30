<?php

namespace App\Filament\Resources\Follows\Pages;

use App\Filament\Resources\Follows\FollowResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFollow extends CreateRecord
{
    protected static string $resource = FollowResource::class;
}
