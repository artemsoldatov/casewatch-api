<?php

use App\Aml\RiskEngine;
use App\Models\Transaction;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->org = makeOrg();
    $this->engine = app(RiskEngine::class);
    $this->base = now()->subDays(3)->startOfHour();
});

function incoming($cp): Collection
{
    return Transaction::where('to_counterparty_id', $cp->id)->orderBy('occurred_at')->get();
}

function outgoing($cp): Collection
{
    return Transaction::where('from_counterparty_id', $cp->id)->orderBy('occurred_at')->get();
}

it('flags structuring on repeated sub-threshold transfers', function (): void {
    $cp = makeCounterparty($this->org);
    $src = makeCounterparty($this->org);
    foreach ([950_000, 970_000, 990_000] as $i => $amount) {
        makeTx($this->org, $src, $cp, $amount, $this->base->copy()->addDays($i));
    }

    $spec = $this->engine->evaluate($cp, incoming($cp), outgoing($cp));

    expect($spec)->not->toBeNull()
        ->and($spec->type())->toBe('STRUCTURING')
        ->and($spec->score())->toBe(45);
});

it('does not flag structuring below three transfers', function (): void {
    $cp = makeCounterparty($this->org);
    $src = makeCounterparty($this->org);
    foreach ([950_000, 970_000] as $i => $amount) {
        makeTx($this->org, $src, $cp, $amount, $this->base->copy()->addDays($i));
    }

    expect($this->engine->evaluate($cp, incoming($cp), outgoing($cp)))->toBeNull();
});

it('flags rapid in-and-out movement', function (): void {
    $cp = makeCounterparty($this->org);
    $src = makeCounterparty($this->org);
    $dst = makeCounterparty($this->org);
    makeTx($this->org, $src, $cp, 5_000_000, $this->base->copy());
    makeTx($this->org, $cp, $dst, 4_500_000, $this->base->copy()->addHours(2));

    $spec = $this->engine->evaluate($cp, incoming($cp), outgoing($cp));

    expect($spec)->not->toBeNull()
        ->and($spec->type())->toBe('RAPID_MOVEMENT');
});

it('flags layering across multiple sources and destinations', function (): void {
    $cp = makeCounterparty($this->org);
    foreach (range(0, 2) as $i) {
        makeTx($this->org, makeCounterparty($this->org), $cp, 2_000_000, $this->base->copy()->addHours($i));
    }
    foreach (range(0, 2) as $i) {
        makeTx($this->org, $cp, makeCounterparty($this->org), 1_700_000, $this->base->copy()->addHours(4 + $i));
    }

    $spec = $this->engine->evaluate($cp, incoming($cp), outgoing($cp));

    // layering (40) dominates the co-occurring rapid-movement finding (35)
    expect($spec)->not->toBeNull()
        ->and($spec->type())->toBe('LAYERING')
        ->and($spec->severity())->toBe('HIGH');
});

it('flags exposure to a high-risk jurisdiction', function (): void {
    $cp = makeCounterparty($this->org, ['country' => 'IR']);
    $src = makeCounterparty($this->org);
    makeTx($this->org, $src, $cp, 800_000, $this->base->copy());

    $spec = $this->engine->evaluate($cp, incoming($cp), outgoing($cp));

    expect($spec)->not->toBeNull()
        ->and($spec->type())->toBe('HIGH_RISK_JURISDICTION');
});

it('leaves ordinary activity alone', function (): void {
    $cp = makeCounterparty($this->org);
    $src = makeCounterparty($this->org);
    makeTx($this->org, $src, $cp, 1_200_000, $this->base->copy());

    expect($this->engine->evaluate($cp, incoming($cp), outgoing($cp)))->toBeNull();
});
