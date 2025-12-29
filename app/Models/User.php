<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $email
 * @property int $credits_balance
 * @method static \Illuminate\Database\Eloquent\Builder|User where(string $column, $value)
 * @method static self firstOrCreate(array $attributes, array $values = [])
 */
class User extends Authenticatable implements CanResetPasswordContract
{
    use CanResetPassword, HasApiTokens, Notifiable;

    public function hasCredits(int $required = 1): bool
    {
        return $this->credits_balance >= $required;
    }

    public function deductCredits(int $amount): void
    {
        DB::transaction(function () use ($amount) {
            $this->decrement('credits_balance', $amount);

            CreditTransaction::create([
                'user_id' => $this->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_after' => $this->credits_balance,
                'description' => 'Email verification',
            ]);
        });
    }

    public function addCredits(int $amount, string $reason = 'Purchase'): void
    {
        DB::transaction(function () use ($amount, $reason) {
            $this->increment('credits_balance', $amount);

            CreditTransaction::create([
                'user_id' => $this->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_after' => $this->credits_balance,
                'description' => $reason,
            ]);
        });
    }
}
