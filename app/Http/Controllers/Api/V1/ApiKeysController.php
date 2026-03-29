<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeysController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $keys = ApiKey::where('tenant_id', $request->user()->tenant_id)
            ->where('is_active', true)
            ->select('id', 'name', 'key_prefix', 'permissions', 'last_used_at', 'expires_at', 'created_at')
            ->latest()
            ->get();

        return $this->success($keys);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'in:read,write,delete,admin',
            'expires_at'  => 'nullable|date|after:now',
        ]);

        $raw    = 'sk_' . Str::random(48);
        $hash   = hash('sha256', $raw);
        $prefix = substr($raw, 0, 8);

        $key = ApiKey::create([
            'tenant_id'   => $request->user()->tenant_id,
            'name'        => $data['name'],
            'key_hash'    => $hash,
            'key_prefix'  => $prefix,
            'permissions' => $data['permissions'],
            'is_active'   => true,
            'expires_at'  => $data['expires_at'] ?? null,
        ]);

        // Return raw key once only
        return $this->created([
            'id'          => $key->id,
            'name'        => $key->name,
            'key'         => $raw,
            'key_prefix'  => $prefix,
            'permissions' => $key->permissions,
            'expires_at'  => $key->expires_at,
            'created_at'  => $key->created_at,
        ]);
    }

    public function revoke(Request $request, int $id): JsonResponse
    {
        $key = ApiKey::where('tenant_id', $request->user()->tenant_id)->find($id);
        if (! $key) return $this->notFound('API key');

        $key->update(['is_active' => false]);
        return $this->noContent();
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate(['key' => 'required|string']);

        $key = ApiKey::findByRawKey($request->key);

        if (! $key || ! $key->isValid()) {
            return $this->unauthorized('Invalid or expired API key');
        }

        $key->markUsed();

        return $this->success([
            'valid'       => true,
            'tenant_id'   => $key->tenant_id,
            'permissions' => $key->permissions,
            'expires_at'  => $key->expires_at,
        ]);
    }
}
