<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\CreditTransaction
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property int $amount
 * @property int $balance_after
 * @method static self create(array $attributes = [])
 */
class CreditTransaction extends Model
{
    protected $fillable = ['user_id', 'type', 'amount', 'balance_after', 'description'];
}
