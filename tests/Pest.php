<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
