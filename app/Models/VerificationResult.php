<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\VerificationResult
 *
 * @property int $id
 * @property string $email
 * @property string $status
 * @property int $risk_score
 * @property array $details
 * @method static self create(array $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Builder|VerificationResult where(string $column, $value)
 */
class VerificationResult extends Model
{
    protected $fillable = ['email', 'status', 'risk_score', 'details', 'job_id', 'user_id'];

    protected $casts = [
        'details' => 'array',
    ];
}
