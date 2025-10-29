<?php

use App\Aml\AlertSpec;
use App\Aml\Finding;

function finding(string $rule, int $weight): Finding
{
    return new Finding($rule, "title-{$rule}", 'detail', [], $weight);
}

it('sums weights and caps the score at 100', function (): void {
    $spec = new AlertSpec('cp-1', [finding('A', 60), finding('B', 60)]);

    expect($spec->score())->toBe(100);
});

it('maps score to severity bands', function (): void {
    expect((new AlertSpec('cp', [finding('A', 70)]))->severity())->toBe('HIGH')
        ->and((new AlertSpec('cp', [finding('A', 40)]))->severity())->toBe('MEDIUM')
        ->and((new AlertSpec('cp', [finding('A', 30)]))->severity())->toBe('LOW');
});

it('picks the heaviest finding as the alert type', function (): void {
    $spec = new AlertSpec('cp', [finding('LIGHT', 20), finding('HEAVY', 45)]);

    expect($spec->type())->toBe('HEAVY');
});
