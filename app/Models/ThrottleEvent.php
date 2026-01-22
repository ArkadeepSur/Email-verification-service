<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ThrottleEvent
 *
 * @property int $id
 * @property string $throttle_key
 * @property string $email
 * @property string $ip
 *
 * @method static self create(array $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ThrottleEvent where(string $column, string $operator = null, $value = null)
 */
class ThrottleEvent extends Model
{
    protected $fillable = ['throttle_key', 'email', 'ip'];
}

