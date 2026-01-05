<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class UsersChart extends ChartWidget
{
    protected ?string $heading = 'Users Chart';
    protected static ?int $sort = 1;
    
    public ?string $filter = '7days';
    
    protected function getFilters(): ?array
    {
        return [
            '7days' => 'Last 7 days',
            '30days' => 'Last 30 days',
            '3months' => 'Last 3 months',
            '6months' => 'Last 6 months',
            '1year' => 'Last 1 year',
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter;
        $data = [];
        $labels = [];
        
        switch ($filter) {
            case '7days':
                for ($i = 6; $i >= 0; $i--) {
                    $date = now()->subDays($i);
                    $count = \App\Models\User::whereDate('created_at', $date)->count();
                    $labels[] = $date->format('M j');
                    $data[] = $count;
                }
                break;
                
            case '30days':
                for ($i = 29; $i >= 0; $i--) {
                    $date = now()->subDays($i);
                    $count = \App\Models\User::whereDate('created_at', $date)->count();
                    $labels[] = $date->format('M j');
                    $data[] = $count;
                }
                break;
                
            case '3months':
                for ($i = 11; $i >= 0; $i--) {
                    $startDate = now()->subWeeks($i)->startOfWeek();
                    $endDate = now()->subWeeks($i)->endOfWeek();
                    $count = \App\Models\User::whereBetween('created_at', [$startDate, $endDate])->count();
                    $labels[] = $startDate->format('M j');
                    $data[] = $count;
                }
                break;
                
            case '6months':
            case '1year':
                $months = $filter === '6months' ? 6 : 12;
                for ($i = $months - 1; $i >= 0; $i--) {
                    $date = now()->subMonths($i);
                    $count = \App\Models\User::whereYear('created_at', $date->year)
                                            ->whereMonth('created_at', $date->month)
                                            ->count();
                    $labels[] = $date->format('M Y');
                    $data[] = $count;
                }
                break;
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'New Users',
                    'data' => $data,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
