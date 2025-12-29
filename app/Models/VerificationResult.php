<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificationResult extends Model
{
    protected $fillable = ['email', 'status', 'risk_score', 'details', 'job_id', 'user_id'];

    protected $casts = [
        'details' => 'array',
    ];
}
