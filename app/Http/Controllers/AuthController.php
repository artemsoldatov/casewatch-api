<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Registers an organisation and its first user, who becomes the lead. A
     * real deployment would invite analysts separately; here the first signup
     * bootstraps the tenant.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $org = Organization::create([
            'name' => $request->string('organization'),
            'slug' => Str::slug($request->string('organization').'-'.Str::random(6)),
        ]);

        $user = User::create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'password' => Hash::make($request->string('password')),
            'organization_id' => $org->id,
            'role' => 'lead',
        ]);

        return response()->json([
            'token' => $user->createToken('api')->plainTextToken,
            'user' => ['id' => $user->id, 'name' => $user->name, 'role' => $user->role],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email'))->first();

        if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages(['email' => 'Invalid credentials']);
        }

        return response()->json([
            'token' => $user->createToken('api')->plainTextToken,
            'user' => ['id' => $user->id, 'name' => $user->name, 'role' => $user->role],
        ]);
    }

    public function logout(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $user->tokens()->delete();

        return response()->json(['ok' => true]);
    }
}
