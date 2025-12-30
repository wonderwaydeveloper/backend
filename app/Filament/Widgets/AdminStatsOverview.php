<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Report;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
                
            Stat::make('Total Posts', Post::count())
                ->description('Published posts')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),
                
            Stat::make('Total Comments', Comment::count())
                ->description('User comments')
                ->descriptionIcon('heroicon-m-chat-bubble-left')
                ->color('warning'),
                
            Stat::make('Pending Reports', Report::where('status', 'pending')->count())
                ->description('Need review')
                ->descriptionIcon('heroicon-m-flag')
                ->color('danger'),
        ];
    }
}