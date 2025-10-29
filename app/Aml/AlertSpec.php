<?php

namespace App\Aml;

final class AlertSpec
{
    /**
     * @param  list<Finding>  $findings
     */
    public function __construct(
        public readonly string $counterpartyId,
        public readonly array $findings,
    ) {}

    public function score(): int
    {
        return min(100, array_sum(array_map(static fn (Finding $f) => $f->weight, $this->findings)));
    }

    public function severity(): string
    {
        $score = $this->score();

        return match (true) {
            $score >= 70 => 'HIGH',
            $score >= 40 => 'MEDIUM',
            default => 'LOW',
        };
    }

    /** the dominant rule drives the alert type */
    public function type(): string
    {
        $top = collect($this->findings)->sortByDesc(fn (Finding $f) => $f->weight)->first();

        return $top === null ? 'UNKNOWN' : $top->rule;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rationale(): array
    {
        return array_map(static fn (Finding $f) => $f->toArray(), $this->findings);
    }
}
