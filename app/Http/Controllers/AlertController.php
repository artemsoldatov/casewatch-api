<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\AuditEvent;
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

    public function audit(Request $request, string $id): JsonResponse
    {
        return response()->json($this->auditFor($this->scopedAlert($request, $id)));
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
