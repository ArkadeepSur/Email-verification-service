<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Blacklist
 *
 * @property int $id
 * @property string $pattern
 * @property bool $is_active
 * @method static \Illuminate\Database\Eloquent\Builder|Blacklist where(string $column, $value)
 * @method static self create(array $attributes = [])
 */
class Blacklist extends Model
{
    protected $fillable = ['pattern', 'description', 'is_active'];
}
