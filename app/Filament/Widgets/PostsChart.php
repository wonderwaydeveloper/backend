<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class PostsChart extends ChartWidget
{
    protected ?string $heading = 'Posts Chart';

    protected function getData(): array
    {
        return [
            //
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
