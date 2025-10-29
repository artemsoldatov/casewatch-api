<?php

namespace App\Aml;

/**
 * A single rule hit: what fired, why, the evidence, and how much it weighs
 * toward the alert score.
 */
final class Finding
{
    /**
     * @param  list<array<string, mixed>>  $evidence
     */
    public function __construct(
        public readonly string $rule,
        public readonly string $title,
        public readonly string $detail,
        public readonly array $evidence,
        public readonly int $weight,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'rule' => $this->rule,
            'title' => $this->title,
            'detail' => $this->detail,
            'evidence' => $this->evidence,
            'weight' => $this->weight,
        ];
    }
}
