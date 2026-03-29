<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function registerTenant(array $data): array
    {
        $tenant = Tenant::create([
            'name'          => $data['tenant_name'],
            'slug'          => Str::slug($data['tenant_slug']),
            'plan'          => 'free',
            'is_active'     => true,
            'trial_ends_at' => Carbon::now()->addDays(14),
        ]);

        $user = User::create([
            'tenant_id'  => $tenant->id,
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
            'role'       => 'owner',
            'is_active'  => true,
        ]);

        $tokens = $this->generateTokenPair($user);

        return [
            'tenant' => $tenant,
            'user'   => $user,
            'tokens' => $tokens,
        ];
    }

    public function login(string $email, string $password, string $tenantSlug): ?array
    {
        $tenant = Tenant::where('slug', $tenantSlug)->where('is_active', true)->first();
        if (! $tenant) return null;

        $user = User::where('email', $email)
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        $user->update(['last_login_at' => now()]);

        return [
            'user'   => $user,
            'tokens' => $this->generateTokenPair($user),
        ];
    }

    public function refresh(string $rawRefreshToken): ?array
    {
        $hash    = hash('sha256', $rawRefreshToken);
        $stored  = RefreshToken::where('token_hash', $hash)->with('user')->first();

        if (! $stored || ! $stored->isValid()) return null;

        $stored->revoke();

        $tokens = $this->generateTokenPair($stored->user);
        return ['user' => $stored->user, 'tokens' => $tokens];
    }

    public function logout(string $rawRefreshToken): void
    {
        $hash = hash('sha256', $rawRefreshToken);
        RefreshToken::where('token_hash', $hash)->each(fn ($t) => $t->revoke());
    }

    private function generateTokenPair(User $user): array
    {
        $accessToken = JWTAuth::fromUser($user);

        $raw    = Str::random(64);
        $hash   = hash('sha256', $raw);

        RefreshToken::create([
            'user_id'    => $user->id,
            'token_hash' => $hash,
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $raw,
            'token_type'    => 'Bearer',
            'expires_in'    => config('jwt.ttl', 60) * 60,
        ];
    }
}
