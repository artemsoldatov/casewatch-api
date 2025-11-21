<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DispositionService
{
    private const STATUS = [
        'clear' => 'CLEARED',
        'escalate' => 'ESCALATED',
        'assign' => 'IN_REVIEW',
    ];

    /**
     * Applies a disposition and records it in the append-only audit trail, both
     * in one transaction so the state change and its audit row commit together.
     */
    public function apply(Alert $alert, User $actor, string $action, ?string $note, ?string $assignee): Alert
    {
        if (! isset(self::STATUS[$action])) {
            throw new RuntimeException("Unknown disposition {$action}");
        }

        return DB::transaction(function () use ($alert, $actor, $action, $note, $assignee): Alert {
            $alert->update([
                'status' => self::STATUS[$action],
                'assigned_to' => $action === 'assign' ? $assignee : $alert->assigned_to,
            ]);

            AuditEvent::create([
                'organization_id' => $alert->organization_id,
                'alert_id' => $alert->id,
                'actor_id' => (string) $actor->id,
                'action' => "alert.{$action}",
                'meta' => array_filter(['note' => $note, 'assignee' => $assignee]),
            ]);

            return $alert->refresh();
        });
    }
}
