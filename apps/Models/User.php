class User extends Authenticatable
{
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
                'description' => 'Email verification'
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
                'description' => $reason
            ]);
        });
    }
}