<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $chain
 * @property string $tx_hash
 * @property string|null $from_counterparty_id
 * @property string|null $to_counterparty_id
 * @property int $amount_cents
 * @property string $currency
 * @property Carbon $occurred_at
 */
class Transaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id', 'chain', 'tx_hash', 'from_counterparty_id', 'to_counterparty_id',
        'amount_cents', 'currency', 'occurred_at',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'occurred_at' => 'datetime',
    ];

    /** @return BelongsTo<Counterparty, $this> */
    public function fromCounterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, 'from_counterparty_id');
    }

    /** @return BelongsTo<Counterparty, $this> */
    public function toCounterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, 'to_counterparty_id');
    }
}
