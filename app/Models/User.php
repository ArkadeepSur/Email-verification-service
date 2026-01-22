<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
class User extends Authenticatable
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
    ];

    /**
     * Get the user's verification results.
     */
    public function verificationResults(): HasMany
    {
        return $this->hasMany(VerificationResult::class);
    }

    /**
     * Get the user's credit transactions.
     */
    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    /**
     * Get the user's webhooks.
     */
    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }

    /**
     * Check if user has enough credits.
     */
    public function hasCredits(int $amount = 1): bool
    {
        return $this->credits_balance >= $amount;
    }

    /**
     * Deduct credits from user balance.
     */
    public function deductCredits(int $amount = 1): bool
    {
        if (! $this->hasCredits($amount)) {
            return false;
        }

        $this->credits_balance -= $amount;
        $this->save();

        CreditTransaction::create([
            'user_id' => $this->id,
            'type' => 'debit',
            'amount' => $amount,
            'balance_after' => $this->credits_balance,
            'description' => 'Email verification',
        ]);

        return true;
    }

    /**
     * Add credits to user balance.
     */
    public function addCredits(int $amount, string $description = 'Credit addition'): bool
    {
        $this->credits_balance += $amount;
        $this->save();

        CreditTransaction::create([
            'user_id' => $this->id,
            'type' => 'credit',
            'amount' => $amount,
            'balance_after' => $this->credits_balance,
            'description' => $description,
        ]);

        return true;
    }
}
