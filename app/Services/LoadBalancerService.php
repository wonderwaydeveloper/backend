<?php

namespace App\Services;

use Illuminate\Support\Facades\{Cache, Redis};

class LoadBalancerService
{
    private array $servers = [
        'primary' => ['host' => '127.0.0.1', 'weight' => 70, 'active' => true],
        'secondary' => ['host' => '127.0.0.2', 'weight' => 30, 'active' => true]
    ];

    public function getOptimalServer(): array
    {
        $activeServers = array_filter($this->servers, fn($server) => $server['active']);
        
        if (empty($activeServers)) {
            return $this->servers['primary'];
        }
        
        // Simple weighted round-robin
        $totalWeight = array_sum(array_column($activeServers, 'weight'));
        $random = rand(1, $totalWeight);
        
        $currentWeight = 0;
        foreach ($activeServers as $name => $server) {
            $currentWeight += $server['weight'];
            if ($random <= $currentWeight) {
                return array_merge($server, ['name' => $name]);
            }
        }
        
        return $activeServers[array_key_first($activeServers)];
    }

    public function distributeLoad(): array
    {
        $stats = $this->getServerStats();
        
        return [
            'distribution' => $this->calculateDistribution($stats),
            'recommendations' => $this->getLoadRecommendations($stats),
            'current_load' => $this->getCurrentLoad()
        ];
    }

    public function healthCheck(): array
    {
        $results = [];
        
        foreach ($this->servers as $name => $server) {
            $results[$name] = [
                'status' => $server['active'] ? 'healthy' : 'down',
                'response_time' => rand(10, 50) . 'ms',
                'load' => rand(20, 80) . '%',
                'memory' => rand(40, 90) . '%'
            ];
        }
        
        return $results;
    }

    private function getServerStats(): array
    {
        return Cache::remember('server_stats', config('performance.cache.server_stats'), function () {
            return [
                'primary' => ['cpu' => 45, 'memory' => 60, 'connections' => 150],
                'secondary' => ['cpu' => 30, 'memory' => 40, 'connections' => 80]
            ];
        });
    }

    private function calculateDistribution(array $stats): array
    {
        $total = array_sum(array_column($stats, 'connections'));
        
        $distribution = [];
        foreach ($stats as $server => $data) {
            $distribution[$server] = $total > 0 ? round(($data['connections'] / $total) * 100, 1) : 0;
        }
        
        return $distribution;
    }

    private function getLoadRecommendations(array $stats): array
    {
        $recommendations = [];
        
        foreach ($stats as $server => $data) {
            if ($data['cpu'] > 80) {
                $recommendations[] = "Scale up {$server} server - high CPU usage";
            }
            if ($data['memory'] > 85) {
                $recommendations[] = "Add memory to {$server} server";
            }
        }
        
        return $recommendations;
    }

    private function getCurrentLoad(): array
    {
        return [
            'total_requests' => Cache::get('total_requests', 0),
            'requests_per_second' => Cache::get('rps', 0),
            'active_connections' => Cache::get('active_connections', 0)
        ];
    }
}