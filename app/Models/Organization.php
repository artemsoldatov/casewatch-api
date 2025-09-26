<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 */
class Organization extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'slug'];
}
