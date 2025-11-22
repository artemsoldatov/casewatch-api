<?php

namespace App\Http\Controllers;

use App\Aml\Assessment;
use App\Http\Requests\DispositionRequest;
use App\Models\Alert;
use App\Models\AuditEvent;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DispositionService;
use App\Services\SarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class AlertController extends Controller
{
    public function __construct(
        private readonly DispositionService $dispositions,
        private readonly SarService $sar,
    ) {}

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

    public function sar(Request $request, string $id): JsonResponse
    {
        return response()->json($this->sar->draft($this->scopedAlert($request, $id)));
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

    public function disposition(DispositionRequest $request, string $id): JsonResponse
    {
        $alert = $this->scopedAlert($request, $id);
        /** @var User $actor */
        $actor = $request->user();
        $action = $request->string('action')->toString();

        // clearing or escalating a case is a lead-only decision
        if (in_array($action, ['clear', 'escalate'], true) && ! $actor->isLead()) {
            return response()->json(['message' => 'Lead role required'], Response::HTTP_FORBIDDEN);
        }

        /** @var string|null $note */
        $note = $request->input('note');
        /** @var string|null $assignee */
        $assignee = $request->input('assignee');

        $alert = $this->dispositions->apply($alert, $actor, $action, $note, $assignee);

        return response()->json(['alert' => $alert]);
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
