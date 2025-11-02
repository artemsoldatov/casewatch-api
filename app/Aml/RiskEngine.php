<?php

namespace App\Aml;

use App\Models\Counterparty;
use App\Models\Transaction;
use Illuminate\Support\Collection;

/**
 * Deterministic AML rules over a counterparty's transaction history. Given the
 * same data it always produces the same findings, which is what makes the
 * output auditable and keeps the tests meaningful.
 */
class RiskEngine
{
    private const STRUCTURING_MIN = 900_000;   // $9,000

    private const STRUCTURING_MAX = 1_000_000; // $10,000 reporting threshold

    private const STRUCTURING_WINDOW_HOURS = 168; // 7 days

    private const RAPID_WINDOW_HOURS = 24;

    /**
     * @param  Collection<int, Transaction>  $incoming
     * @param  Collection<int, Transaction>  $outgoing
     */
    public function evaluate(Counterparty $cp, Collection $incoming, Collection $outgoing): ?AlertSpec
    {
        $findings = array_filter([
            $this->structuring($incoming->merge($outgoing)),
            $this->rapidMovement($incoming, $outgoing),
            $this->layering($incoming, $outgoing),
        ]);

        if ($findings === []) {
            return null;
        }

        return new AlertSpec($cp->id, array_values($findings));
    }

    /**
     * @param  Collection<int, Transaction>  $txns
     */
    private function structuring(Collection $txns): ?Finding
    {
        $near = $txns
            ->filter(fn (Transaction $t) => $t->amount_cents >= self::STRUCTURING_MIN
                && $t->amount_cents < self::STRUCTURING_MAX)
            ->sortBy('occurred_at')
            ->values()
            ->all();

        // a sliding window with >= 3 sub-threshold transfers looks like structuring
        for ($i = 0; $i + 2 < count($near); $i++) {
            $start = $near[$i]->occurred_at;
            $window = collect($near)->filter(
                fn (Transaction $t) => $t->occurred_at->diffInHours($start, true) <= self::STRUCTURING_WINDOW_HOURS
            );
            if ($window->count() >= 3) {
                return new Finding(
                    'STRUCTURING',
                    'Sub-threshold transactions',
                    "{$window->count()} transfers just under the reporting threshold within 7 days",
                    $this->evidence($window),
                    45,
                );
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, Transaction>  $incoming
     * @param  Collection<int, Transaction>  $outgoing
     */
    private function rapidMovement(Collection $incoming, Collection $outgoing): ?Finding
    {
        $in = (int) $incoming->sum(fn (Transaction $t): int => $t->amount_cents);
        $out = (int) $outgoing->sum(fn (Transaction $t): int => $t->amount_cents);
        if ($in === 0 || $out < (int) ($in * 0.8)) {
            return null;
        }

        // an inflow quickly followed by an outflow is pass-through behaviour
        foreach ($incoming as $rx) {
            $fast = $outgoing->first(
                fn (Transaction $t) => $t->occurred_at->gte($rx->occurred_at)
                    && $t->occurred_at->diffInHours($rx->occurred_at, true) <= self::RAPID_WINDOW_HOURS
            );
            if ($fast !== null) {
                return new Finding(
                    'RAPID_MOVEMENT',
                    'Rapid in-and-out movement',
                    'Funds moved out within 24h of arriving, ~'.(int) round($out / $in * 100).'% passed through',
                    $this->evidence(collect([$rx, $fast])),
                    35,
                );
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, Transaction>  $incoming
     * @param  Collection<int, Transaction>  $outgoing
     */
    private function layering(Collection $incoming, Collection $outgoing): ?Finding
    {
        $sources = $incoming->pluck('from_counterparty_id')->filter()->unique();
        $dests = $outgoing->pluck('to_counterparty_id')->filter()->unique();
        if ($sources->count() >= 2 && $dests->count() >= 2) {
            return new Finding(
                'LAYERING',
                'Intermediary layering',
                "Funds fan in from {$sources->count()} sources and out to {$dests->count()} destinations",
                $this->evidence($incoming->merge($outgoing)),
                40,
            );
        }

        return null;
    }

    /**
     * @param  Collection<int, Transaction>  $txns
     * @return list<array<string, mixed>>
     */
    private function evidence(Collection $txns): array
    {
        return array_values($txns
            ->take(5)
            ->map(fn (Transaction $t) => [
                'tx_hash' => $t->tx_hash,
                'amount_cents' => $t->amount_cents,
                'chain' => $t->chain,
                'occurred_at' => $t->occurred_at->toIso8601String(),
            ])
            ->all());
    }
}
