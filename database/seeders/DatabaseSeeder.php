<?php

namespace Database\Seeders;

use App\Aml\AlertGenerator;
use App\Models\Counterparty;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Builds one demo tenant with synthetic activity that deliberately trips each
 * AML rule, then runs the generator so the reviewer console has real alerts to
 * work. Everything is deterministic given a fixed base time.
 */
class DatabaseSeeder extends Seeder
{
    private Organization $org;

    private Carbon $base;

    public function run(AlertGenerator $generator): void
    {
        $this->org = Organization::create([
            'name' => 'Northgate Compliance',
            'slug' => 'northgate-'.Str::random(6),
        ]);
        $this->base = now()->subDays(3)->startOfHour();

        User::create([
            'name' => 'Dana Lead',
            'email' => 'lead@casewatch.test',
            'password' => Hash::make('password'),
            'organization_id' => $this->org->id,
            'role' => 'lead',
        ]);
        User::create([
            'name' => 'Alex Analyst',
            'email' => 'analyst@casewatch.test',
            'password' => Hash::make('password'),
            'organization_id' => $this->org->id,
            'role' => 'analyst',
        ]);

        $this->structuringCase();
        $this->rapidMovementCase();
        $this->layeringCase();
        $this->highRiskCase();
        $this->cleanCase();

        $opened = $generator->run($this->org->id);
        $this->command->info("Opened {$opened} alerts for {$this->org->name}.");
    }

    private function structuringCase(): void
    {
        $cp = $this->counterparty('smurf-01', 'Quiet River LLC', 'entity', 'US', 'ethereum');
        $src = $this->counterparty('src-struct', 'Payer A', 'individual', 'US', 'ethereum');
        foreach ([950_000, 970_000, 990_000] as $i => $amount) {
            $this->tx($src, $cp, $amount, $this->base->copy()->addDays($i));
        }
    }

    private function rapidMovementCase(): void
    {
        $cp = $this->counterparty('passthru-01', 'Transit Holdings', 'entity', 'US', 'tron');
        $src = $this->counterparty('src-rapid', 'Origin Fund', 'exchange', 'US', 'tron');
        $dst = $this->counterparty('dst-rapid', 'Exit Wallet', 'individual', 'US', 'tron');
        $this->tx($src, $cp, 5_000_000, $this->base->copy());
        $this->tx($cp, $dst, 4_500_000, $this->base->copy()->addHours(2));
    }

    private function layeringCase(): void
    {
        $cp = $this->counterparty('layer-01', 'Mesh Intermediary', 'entity', 'US', 'ethereum');
        foreach (['a', 'b', 'c'] as $i => $tag) {
            $src = $this->counterparty("layer-src-{$tag}", "Source {$tag}", 'individual', 'US', 'ethereum');
            $this->tx($src, $cp, 2_000_000, $this->base->copy()->addHours($i));
        }
        foreach (['x', 'y', 'z'] as $i => $tag) {
            $dst = $this->counterparty("layer-dst-{$tag}", "Dest {$tag}", 'individual', 'US', 'ethereum');
            $this->tx($cp, $dst, 1_700_000, $this->base->copy()->addHours(4 + $i));
        }
    }

    private function highRiskCase(): void
    {
        $cp = $this->counterparty('hrj-01', 'Sanctioned Trade Co', 'entity', 'IR', 'bitcoin');
        $src = $this->counterparty('src-hrj', 'Broker Ltd', 'exchange', 'AE', 'bitcoin');
        $this->tx($src, $cp, 800_000, $this->base->copy());
    }

    private function cleanCase(): void
    {
        $cp = $this->counterparty('clean-01', 'Acme Payroll', 'entity', 'US', 'ethereum');
        $src = $this->counterparty('src-clean', 'Client Corp', 'entity', 'US', 'ethereum');
        $this->tx($src, $cp, 1_200_000, $this->base->copy());
    }

    private function counterparty(string $ref, string $name, string $kind, string $country, string $chain): Counterparty
    {
        return Counterparty::create([
            'organization_id' => $this->org->id,
            'external_ref' => $ref,
            'name' => $name,
            'kind' => $kind,
            'country' => $country,
            'chain' => $chain,
            'wallet_address' => '0x'.Str::random(40),
        ]);
    }

    private function tx(Counterparty $from, Counterparty $to, int $amountCents, Carbon $at): void
    {
        Transaction::create([
            'organization_id' => $this->org->id,
            'chain' => $to->chain,
            'tx_hash' => '0x'.Str::random(64),
            'from_counterparty_id' => $from->id,
            'to_counterparty_id' => $to->id,
            'amount_cents' => $amountCents,
            'currency' => 'USD',
            'occurred_at' => $at,
        ]);
    }
}
