<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $external_ref
 * @property string $name
 * @property string $kind
 * @property string $country
 * @property string $chain
 * @property string $wallet_address
 */
class Counterparty extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id', 'external_ref', 'name', 'kind', 'country', 'chain', 'wallet_address',
    ];

    protected $casts = [
        // PII encrypted at rest
        'name' => 'encrypted',
    ];
}
