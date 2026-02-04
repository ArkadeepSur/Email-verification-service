<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property int $credits_balance
 * @property string|null $email_verified_at
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'credits_balance',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'credits_balance' => 'integer',
    ];

    public function verificationResults(): HasMany
    {
        return $this->hasMany(VerificationResult::class);
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }

    public function hasCredits(int $amount = 1): bool
    {
        return $this->credits_balance >= $amount;
    }

    public function deductCredits(int $amount = 1): bool
    {
        if (! $this->hasCredits($amount)) {
            return false;
        }

        return DB::transaction(function () use ($amount) {
            $user = User::where('id', $this->id)->lockForUpdate()->first();
            if (! $user || ! $user->hasCredits($amount)) {
                return false;
            }

            $user->credits_balance -= $amount;
            $user->save();

            CreditTransaction::create([
                'user_id' => $user->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_after' => $user->credits_balance,
                'description' => 'Email verification',
            ]);

            return true;
        });
    }

    public function addCredits(int $amount, string $description = 'Credit addition'): bool
    {
        return DB::transaction(function () use ($amount, $description) {
            $user = User::where('id', $this->id)->lockForUpdate()->first();
            if (! $user) {
                return false;
            }

            $user->credits_balance += $amount;
            $user->save();

            CreditTransaction::create([
                'user_id' => $user->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_after' => $user->credits_balance,
                'description' => $description,
            ]);

            return true;
        });
    }
}
