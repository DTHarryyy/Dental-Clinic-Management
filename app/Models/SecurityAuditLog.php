<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Security audit logs are append-only.'));
        static::deleting(fn () => throw new \LogicException('Security audit logs are append-only.'));
    }

    protected $guarded = [];

    protected $casts = [
        'context' => 'array',
    ];
}
