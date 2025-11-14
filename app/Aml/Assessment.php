<?php

namespace App\Aml;

use App\Models\Alert;

/**
 * Turns an alert's rule findings into a structured assessment. Deterministic
 * on purpose: the recommendation follows from the score, not a separate model.
 */
class Assessment
{
    /**
     * @return array<string, mixed>
     */
    public static function for(Alert $alert): array
    {
        return [
            'score' => $alert->score,
            'severity' => $alert->severity,
            'recommendation' => self::recommendation($alert->score),
            'summary' => self::summary($alert),
            'factors' => $alert->rationale,
        ];
    }

    private static function recommendation(int $score): string
    {
        return match (true) {
            $score >= 70 => 'escalate',
            $score >= 40 => 'review',
            default => 'monitor',
        };
    }

    private static function summary(Alert $alert): string
    {
        $rules = collect($alert->rationale)
            ->map(fn (array $f): string => is_string($f['title'] ?? null) ? $f['title'] : '')
            ->filter()
            ->implode('; ');

        return "Score {$alert->score}/100 ({$alert->severity}). Triggered: {$rules}.";
    }
}
