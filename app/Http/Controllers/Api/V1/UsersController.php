<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $users = User::forTenant($request->user()->tenant_id)
            ->select('id', 'first_name', 'last_name', 'email', 'role', 'is_active', 'last_login_at', 'created_at')
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->paginated($users);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = User::forTenant($request->user()->tenant_id)->find($id);
        if (! $user) return $this->notFound('User');

        return $this->success($user->only('id', 'first_name', 'last_name', 'email', 'role', 'is_active', 'last_login_at', 'created_at'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'      => ['required', 'email', Rule::unique('users')->where('tenant_id', $request->user()->tenant_id)],
            'first_name' => 'required|string|max:50',
            'last_name'  => 'required|string|max:50',
            'password'   => 'required|string|min:8',
            'role'       => 'required|in:admin,member,viewer',
        ]);

        $user = User::create([
            ...$data,
            'tenant_id' => $request->user()->tenant_id,
            'password'  => Hash::make($data['password']),
            'is_active' => true,
        ]);

        return $this->created($user->only('id', 'first_name', 'last_name', 'email', 'role'));
    }

    public function updateRole(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['role' => 'required|in:admin,member,viewer']);

        $user = User::forTenant($request->user()->tenant_id)->find($id);
        if (! $user) return $this->notFound('User');
        if ($user->id === $request->user()->id) return $this->error('Cannot change own role', 422, 'SELF_ROLE_CHANGE');
        if ($user->role === 'owner') return $this->forbidden('Cannot change owner role');

        $user->update(['role' => $data['role']]);
        return $this->success($user->only('id', 'email', 'role'));
    }

    public function deactivate(Request $request, int $id): JsonResponse
    {
        $user = User::forTenant($request->user()->tenant_id)->find($id);
        if (! $user) return $this->notFound('User');
        if ($user->id === $request->user()->id) return $this->error('Cannot deactivate yourself', 422, 'SELF_DEACTIVATE');
        if ($user->role === 'owner') return $this->forbidden('Cannot deactivate owner');

        $user->update(['is_active' => false]);
        $user->tokens()->delete();
        $user->refreshTokens()->each(fn ($t) => $t->revoke());

        return $this->noContent();
    }
}
