<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterTenantRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RefreshTokenRequest;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthService $authService)
    {
    }

    public function register(RegisterTenantRequest $request): JsonResponse
    {
        $result = $this->authService->registerTenant($request->validated());
        return $this->created([
            'tenant' => [
                'id'   => $result['tenant']->id,
                'name' => $result['tenant']->name,
                'slug' => $result['tenant']->slug,
                'plan' => $result['tenant']->plan,
            ],
            'user'   => $this->formatUser($result['user']),
            'tokens' => $result['tokens'],
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->email,
            $request->password,
            $request->tenant_slug
        );

        if (! $result) {
            return $this->unauthorized('Invalid credentials');
        }

        return $this->success([
            'user'   => $this->formatUser($result['user']),
            'tokens' => $result['tokens'],
        ]);
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $result = $this->authService->refresh($request->refresh_token);

        if (! $result) {
            return $this->unauthorized('Invalid or expired refresh token');
        }

        return $this->success(['tokens' => $result['tokens']]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->validate(['refresh_token' => 'required|string']);
        $this->authService->logout($request->refresh_token);
        return $this->noContent();
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('tenant');

        return $this->success([
            ...$this->formatUser($user),
            'tenant' => [
                'id'   => $user->tenant->id,
                'name' => $user->tenant->name,
                'slug' => $user->tenant->slug,
                'plan' => $user->tenant->plan,
            ],
        ]);
    }

    private function formatUser($user): array
    {
        return [
            'id'         => $user->id,
            'email'      => $user->email,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'full_name'  => $user->full_name,
            'role'       => $user->role,
        ];
    }
}
