<?php

namespace App\DTOs\Response;

class AnalyticsResponseDTO
{
    public function __construct(
        public readonly array $metrics,
        public readonly array $charts,
        public readonly string $period,
        public readonly ?array $comparisons = null,
        public readonly ?array $insights = null
    ) {}

    public static function create(array $metrics, string $period = '30d'): self
    {
        return new self(
            metrics: $metrics,
            charts: [],
            period: $period
        );
    }

    public function withCharts(array $charts): self
    {
        return new self(
            metrics: $this->metrics,
            charts: $charts,
            period: $this->period,
            comparisons: $this->comparisons,
            insights: $this->insights
        );
    }

    public function withComparisons(array $comparisons): self
    {
        return new self(
            metrics: $this->metrics,
            charts: $this->charts,
            period: $this->period,
            comparisons: $comparisons,
            insights: $this->insights
        );
    }

    public function withInsights(array $insights): self
    {
        return new self(
            metrics: $this->metrics,
            charts: $this->charts,
            period: $this->period,
            comparisons: $this->comparisons,
            insights: $insights
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'metrics' => $this->metrics,
            'charts' => $this->charts,
            'period' => $this->period,
            'comparisons' => $this->comparisons,
            'insights' => $this->insights,
        ], fn($value) => $value !== null && $value !== []);
    }
}