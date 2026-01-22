<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Webhook
 *
 * @property int $id
 * @property string $url
 * @property string $event
 * @property string|null $secret
 * @property bool $is_active
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Webhook where(string $column, $value)
 * @method static self create(array $attributes = [])
 */
class Webhook extends Model
{
    protected $fillable = ['user_id', 'url', 'event', 'secret', 'is_active'];

    /**
     * Get the user that owns this webhook.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }}