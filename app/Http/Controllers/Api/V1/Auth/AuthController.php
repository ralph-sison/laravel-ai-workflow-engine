<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\RegisterTenantAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterTenantAction $action): JsonResponse
    {
        $result = $action->execute($request->validated());

        return response()->json([
            'data' => [
                'user' => new UserResource($result['user']),
                'tenant' => [
                    'id' => $result['tenant']->id,
                    'name' => $result['tenant']->name,
                    'slug' => $result['tenant']->slug,
                ],
                'token' => $result['token'],
            ],
            'message' => 'Registration successful.',
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($request->user()->load('tenant')),
        ]);
    }
}
