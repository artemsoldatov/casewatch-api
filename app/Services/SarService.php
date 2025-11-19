<?php

namespace App\Services;

use App\Models\Alert;

/**
 * Drafts a Suspicious Activity Report from an alert. Deterministic template —
 * an analyst edits and files it; the app never files on its own.
 */
class SarService
{
    /**
     * @return array<string, mixed>
     */
    public function draft(Alert $alert): array
    {
        $cp = $alert->counterparty;

        $narrative = collect($alert->rationale)
            ->map(fn (array $f): string => is_string($f['detail'] ?? null) ? '- '.$f['detail'] : '')
            ->filter()
            ->implode("\n");

        return [
            'subject' => [
                'counterparty_id' => $alert->counterparty_id,
                'name' => $cp?->name,
                'country' => $cp?->country,
                'wallet' => $cp?->wallet_address,
            ],
            'alert' => [
                'type' => $alert->type,
                'severity' => $alert->severity,
                'score' => $alert->score,
            ],
            'narrative' => "Automated monitoring flagged the following activity:\n{$narrative}",
            'disclaimer' => 'Draft for analyst review — not filed automatically.',
        ];
    }
}
