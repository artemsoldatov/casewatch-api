<?php

namespace App\Http\Controllers;

use App\Aml\Assessment;
use App\Models\Alert;
use App\Models\AuditEvent;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Alert::query()
            ->where('organization_id', $this->orgId($request))
            ->with('counterparty:id,external_ref,country,kind,chain')
            ->orderByDesc('score')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->string('severity'));
        }

        return response()->json($query->paginate(25));
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $alert = $this->scopedAlert($request, $id);
        $alert->load('counterparty');

        return response()->json([
            'alert' => $alert,
            'audit' => $this->auditFor($alert),
        ]);
    }

    public function assessment(Request $request, string $id): JsonResponse
    {
        return response()->json(Assessment::for($this->scopedAlert($request, $id)));
    }

    public function audit(Request $request, string $id): JsonResponse
    {
        return response()->json($this->auditFor($this->scopedAlert($request, $id)));
    }

    /**
     * The counterparty's transaction timeline, direction relative to them.
     */
    public function transactions(Request $request, string $id): JsonResponse
    {
        $alert = $this->scopedAlert($request, $id);
        $cpId = $alert->counterparty_id;

        $rows = Transaction::query()
            ->where('organization_id', $alert->organization_id)
            ->where(function ($q) use ($cpId): void {
                $q->where('from_counterparty_id', $cpId)->orWhere('to_counterparty_id', $cpId);
            })
            ->orderBy('occurred_at')
            ->get()
            ->map(fn (Transaction $t): array => [
                'id' => $t->id,
                'direction' => $t->to_counterparty_id === $cpId ? 'in' : 'out',
                'chain' => $t->chain,
                'tx_hash' => $t->tx_hash,
                'amount_cents' => $t->amount_cents,
                'currency' => $t->currency,
                'occurred_at' => $t->occurred_at->toIso8601String(),
            ])
            ->values();

        return response()->json(['data' => $rows]);
    }

    private function orgId(Request $request): string
    {
        /** @var User $user */
        $user = $request->user();

        return $user->organization_id;
    }

    private function scopedAlert(Request $request, string $id): Alert
    {
        // scope by org so a cross-tenant id is indistinguishable from a missing one
        return Alert::query()
            ->where('organization_id', $this->orgId($request))
            ->findOrFail($id);
    }

    /**
     * @return Collection<int, AuditEvent>
     */
    private function auditFor(Alert $alert): Collection
    {
        return AuditEvent::query()
            ->where('alert_id', $alert->id)
            ->orderBy('created_at')
            ->get();
    }
}
