<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\VerificationResult
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $email
 * @property string $status
 * @property int $risk_score
 * @property array $details
 * @property bool $syntax_valid
 * @property string|null $smtp
 * @property bool $catch_all
 * @property bool $disposable
 *
 * @method static self create(array $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Builder|VerificationResult where(string $column, string $operator = null, $value = null)
 */
class VerificationResult extends Model
{
    protected $fillable = ['email', 'status', 'risk_score', 'details', 'job_id', 'user_id', 'syntax_valid', 'smtp', 'catch_all', 'disposable'];

    protected $casts = [
        'details' => 'array',
        'syntax_valid' => 'boolean',
        'catch_all' => 'boolean',
        'disposable' => 'boolean',
    ];

    /**
     * Get the user that owns this verification result.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
