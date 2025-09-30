<?php

use App\Models\Organization;
use App\Models\User;

it('registers an organisation and returns a token', function (): void {
    $res = $this->postJson('/api/auth/register', [
        'organization' => 'Northgate',
        'name' => 'Dana',
        'email' => 'dana@casewatch.test',
        'password' => 'secret123',
    ]);

    $res->assertCreated()->assertJsonStructure(['token', 'user' => ['id', 'name', 'role']]);
    expect(Organization::count())->toBe(1)
        ->and(User::first()->role)->toBe('lead');
});

it('logs in with valid credentials', function (): void {
    $org = makeOrg();
    $user = makeUser($org, 'analyst');

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk()->assertJsonStructure(['token']);
});

it('rejects bad credentials', function (): void {
    $user = makeUser(makeOrg());

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'wrong',
    ])->assertStatus(422);
});

it('blocks unauthenticated access to alerts', function (): void {
    $this->getJson('/api/alerts')->assertUnauthorized();
});
