<?php

use App\Models\Alert;
use App\Models\AuditEvent;
use App\Models\Organization;
use Laravel\Sanctum\Sanctum;

function makeAlert(Organization $org, array $attrs = []): Alert
{
    $cp = makeCounterparty($org);

    return Alert::create(array_merge([
        'organization_id' => $org->id,
        'counterparty_id' => $cp->id,
        'type' => 'STRUCTURING',
        'severity' => 'MEDIUM',
        'score' => 45,
        'rationale' => [['rule' => 'STRUCTURING', 'title' => 'Sub-threshold transactions', 'detail' => 'x', 'weight' => 45]],
        'dedup_key' => 'cp:'.$cp->id,
    ], $attrs));
}

it('lists only alerts from the caller organisation', function (): void {
    $org = makeOrg('Mine');
    $other = makeOrg('Theirs');
    makeAlert($org);
    makeAlert($other);
    Sanctum::actingAs(makeUser($org));

    $res = $this->getJson('/api/alerts')->assertOk();

    expect($res->json('total'))->toBe(1);
});

it('hides another tenant alert behind a 404', function (): void {
    $org = makeOrg();
    $foreign = makeAlert(makeOrg('Other'));
    Sanctum::actingAs(makeUser($org));

    $this->getJson("/api/alerts/{$foreign->id}")->assertNotFound();
});

it('shows an alert with its audit trail', function (): void {
    $org = makeOrg();
    $alert = makeAlert($org);
    AuditEvent::create(['organization_id' => $org->id, 'alert_id' => $alert->id, 'action' => 'alert.opened']);
    Sanctum::actingAs(makeUser($org));

    $this->getJson("/api/alerts/{$alert->id}")
        ->assertOk()
        ->assertJsonPath('alert.id', $alert->id)
        ->assertJsonCount(1, 'audit');
});
