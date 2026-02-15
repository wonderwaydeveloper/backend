<?php

namespace App\Services;

use App\Models\{ABTest, ABTestEvent, ABTestParticipant, User};
use Illuminate\Support\Facades\Cache;

class ABTestingService
{
    public function createTest(array $data): ABTest
    {
        return ABTest::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'variants' => $data['variants'],
            'traffic_percentage' => $data['traffic_percentage'] ?? 50,
            'targeting_rules' => $data['targeting_rules'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'status' => 'draft',
        ]);
    }

    public function assignUserToTest(string $testName, User $user): ?string
    {
        $test = $this->getActiveTest($testName);

        if (!$test) {
            return null;
        }

        $existing = ABTestParticipant::where('ab_test_id', $test->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return $existing->variant;
        }

        if (rand(1, 100) > $test->traffic_percentage) {
            return null;
        }

        $variants = array_keys($test->variants);
        $variant = $variants[crc32($user->id . $test->name) % count($variants)];

        ABTestParticipant::create([
            'ab_test_id' => $test->id,
            'user_id' => $user->id,
            'variant' => $variant,
            'assigned_at' => now(),
        ]);

        return $variant;
    }

    public function trackEvent(string $testName, User $user, string $eventType, ?array $eventData = null): bool
    {
        $test = $this->getActiveTest($testName);

        if (!$test) {
            return false;
        }

        $participant = ABTestParticipant::where('ab_test_id', $test->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$participant) {
            return false;
        }

        ABTestEvent::create([
            'ab_test_id' => $test->id,
            'user_id' => $user->id,
            'variant' => $participant->variant,
            'event_type' => $eventType,
            'event_data' => $eventData,
        ]);

        return true;
    }

    public function getTestResults(ABTest $test): array
    {
        $results = ABTestEvent::where('ab_test_id', $test->id)
            ->selectRaw('variant, event_type, COUNT(*) as count, COUNT(DISTINCT user_id) as unique_users')
            ->groupBy(['variant', 'event_type'])
            ->get()
            ->groupBy('variant');

        $participants = ABTestParticipant::where('ab_test_id', $test->id)
            ->selectRaw('variant, COUNT(*) as total')
            ->groupBy('variant')
            ->pluck('total', 'variant');

        return [
            'test' => $test,
            'participants' => $participants,
            'results' => $results,
            'conversion_rates' => $this->calculateConversionRates($results, $participants),
            'statistical_significance' => $this->calculateStatisticalSignificance($results, $participants),
        ];
    }

    public function startTest(ABTest $test): void
    {
        $test->update([
            'status' => 'active',
            'starts_at' => now(),
        ]);

        Cache::forget("ab_test_{$test->name}");
    }

    public function stopTest(ABTest $test): void
    {
        $test->update([
            'status' => 'completed',
            'ends_at' => now(),
        ]);

        Cache::forget("ab_test_{$test->name}");
    }

    private function getActiveTest(string $testName): ?ABTest
    {
        return Cache::remember("ab_test_{$testName}", 300, function () use ($testName) {
            return ABTest::where('name', $testName)
                ->where('status', 'active')
                ->where('starts_at', '<=', now())
                ->where(function ($q) {
                    $q->whereNull('ends_at')
                      ->orWhere('ends_at', '>', now());
                })
                ->first();
        });
    }

    private function calculateConversionRates($results, $participants): array
    {
        $rates = [];

        foreach ($participants as $variant => $total) {
            $conversions = $results->get($variant, collect())
                ->where('event_type', 'conversion')
                ->first();
            
            $conversionCount = $conversions ? $conversions->unique_users : 0;

            $rates[$variant] = $total > 0
                ? round(($conversionCount / $total) * 100, 2)
                : 0;
        }

        return $rates;
    }

    private function calculateStatisticalSignificance($results, $participants): array
    {
        if ($participants->count() < 2) {
            return ['significant' => false, 'confidence' => 0];
        }

        $variants = $participants->keys()->toArray();
        $variantA = $variants[0];
        $variantB = $variants[1];

        $conversionsA = $results->get($variantA, collect())
            ->where('event_type', 'conversion')
            ->first()?->unique_users ?? 0;
        $conversionsB = $results->get($variantB, collect())
            ->where('event_type', 'conversion')
            ->first()?->unique_users ?? 0;

        $totalA = $participants->get($variantA, 0);
        $totalB = $participants->get($variantB, 0);

        if ($totalA < 100 || $totalB < 100) {
            return ['significant' => false, 'confidence' => 0, 'message' => 'Insufficient sample size'];
        }

        $pA = $totalA > 0 ? $conversionsA / $totalA : 0;
        $pB = $totalB > 0 ? $conversionsB / $totalB : 0;
        $pPool = ($conversionsA + $conversionsB) / ($totalA + $totalB);

        $se = sqrt($pPool * (1 - $pPool) * (1/$totalA + 1/$totalB));
        $zScore = $se > 0 ? abs($pA - $pB) / $se : 0;

        $significant = $zScore > 1.96;
        $confidence = min(99.9, (1 - exp(-$zScore * $zScore / 2)) * 100);

        return [
            'significant' => $significant,
            'confidence' => round($confidence, 2),
            'z_score' => round($zScore, 4),
            'winner' => $pA > $pB ? $variantA : $variantB,
        ];
    }
}
