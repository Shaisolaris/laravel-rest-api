<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'key_hash',
        'key_prefix',
        'permissions',
        'is_active',
        'expires_at',
        'last_used_at',
    ];

    protected $casts = [
        'permissions'  => 'array',
        'is_active'    => 'boolean',
        'expires_at'   => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = ['key_hash'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isValid(): bool
    {
        return $this->is_active &&
            ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true)
            || in_array('admin', $this->permissions ?? [], true);
    }

    public function markUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    public static function findByRawKey(string $rawKey): ?self
    {
        $hash = hash('sha256', $rawKey);
        return static::where('key_hash', $hash)->where('is_active', true)->first();
    }
}
