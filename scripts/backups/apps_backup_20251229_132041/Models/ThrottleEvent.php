<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThrottleEvent extends Model
{
    protected $fillable = ['throttle_key', 'email', 'ip'];
}
