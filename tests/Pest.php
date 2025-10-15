<?php

use App\Models\Counterparty;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

function makeOrg(string $name = 'Acme'): Organization
{
    return Organization::create(['name' => $name, 'slug' => Str::slug($name).'-'.Str::random(6)]);
}

function makeUser(Organization $org, string $role = 'analyst'): User
{
    return User::create([
        'name' => ucfirst($role),
        'email' => Str::random(8).'@casewatch.test',
        'password' => Hash::make('password'),
        'organization_id' => $org->id,
        'role' => $role,
    ]);
}

function makeCounterparty(Organization $org, array $attrs = []): Counterparty
{
    return Counterparty::create(array_merge([
        'organization_id' => $org->id,
        'external_ref' => 'cp-'.Str::random(6),
        'name' => 'Counterparty '.Str::random(4),
        'kind' => 'entity',
        'country' => 'US',
        'chain' => 'ethereum',
        'wallet_address' => '0x'.Str::random(40),
    ], $attrs));
}

function makeTx(Organization $org, ?Counterparty $from, ?Counterparty $to, int $amountCents, Carbon $at): Transaction
{
    return Transaction::create([
        'organization_id' => $org->id,
        'chain' => 'ethereum',
        'tx_hash' => '0x'.Str::random(64),
        'from_counterparty_id' => $from?->id,
        'to_counterparty_id' => $to?->id,
        'amount_cents' => $amountCents,
        'currency' => 'USD',
        'occurred_at' => $at,
    ]);
}
