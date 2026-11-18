<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Log the user in.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (! $this->authService->login($request->validated())) {
            return response()->json([
                'data'    => null,
                'message' => 'Invalid credentials',
            ], 401);
        }

        $request->session()->regenerate();

        return response()->json([
            'data'    => new UserResource($request->user()),
            'message' => 'ok',
        ]);
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout();

        return response()->json([
            'data'    => null,
            'message' => 'ok',
        ]);
    }

    /**
     * Get the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data'    => new UserResource($request->user()),
            'message' => 'ok',
        ]);
    }
}
