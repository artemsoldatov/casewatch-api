<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Append-only. Never updated or deleted — the trail is the record.
 *
 * @property string $id
 * @property string $organization_id
 * @property string|null $alert_id
 * @property string|null $actor_id
 * @property string $action
 * @property array<string, mixed>|null $meta
 */
class AuditEvent extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = ['organization_id', 'alert_id', 'actor_id', 'action', 'meta'];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];
}
