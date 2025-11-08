<?php

namespace App\Aml;

use App\Models\Alert;
use App\Models\AuditEvent;
use App\Models\Counterparty;
use App\Models\Transaction;

/**
 * Runs the risk engine over an organisation's counterparties and opens (or
 * refreshes) one alert per risky counterparty. Alerts are deduplicated by
 * counterparty so re-running the ingest is idempotent.
 */
class AlertGenerator
{
    public function __construct(private readonly RiskEngine $engine) {}

    public function run(string $organizationId): int
    {
        $opened = 0;

        Counterparty::query()
            ->where('organization_id', $organizationId)
            ->each(function (Counterparty $cp) use ($organizationId, &$opened): void {
                $incoming = Transaction::query()
                    ->where('to_counterparty_id', $cp->id)->orderBy('occurred_at')->get();
                $outgoing = Transaction::query()
                    ->where('from_counterparty_id', $cp->id)->orderBy('occurred_at')->get();

                $spec = $this->engine->evaluate($cp, $incoming, $outgoing);
                if ($spec === null) {
                    return;
                }

                $alert = Alert::updateOrCreate(
                    ['dedup_key' => "cp:{$cp->id}"],
                    [
                        'organization_id' => $organizationId,
                        'counterparty_id' => $cp->id,
                        'type' => $spec->type(),
                        'severity' => $spec->severity(),
                        'score' => $spec->score(),
                        'rationale' => $spec->rationale(),
                    ],
                );

                if ($alert->wasRecentlyCreated) {
                    AuditEvent::create([
                        'organization_id' => $organizationId,
                        'alert_id' => $alert->id,
                        'action' => 'alert.opened',
                        'meta' => ['type' => $spec->type(), 'score' => $spec->score()],
                    ]);
                    $opened++;
                }
            });

        return $opened;
    }
}
