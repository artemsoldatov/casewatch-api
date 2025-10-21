<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $counterparty_id
 * @property string $type
 * @property string $severity
 * @property int $score
 * @property list<array<string, mixed>> $rationale
 * @property string $dedup_key
 */
class Alert extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id', 'counterparty_id', 'type', 'severity', 'score',
        'rationale', 'dedup_key', 'opened_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'rationale' => 'array',
        'opened_at' => 'datetime',
    ];

    /** @return BelongsTo<Counterparty, $this> */
    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class);
    }
}
