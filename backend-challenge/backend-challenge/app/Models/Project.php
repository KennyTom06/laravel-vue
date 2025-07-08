<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'wallet_balance',
        'investment_goal',
        'promoter_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'wallet_balance' => 'integer',
        'investment_goal' => 'integer',
        'promoter_id' => 'integer',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * The model's validation rules.
     *
     * @return array<string, mixed>
     */
    public static function validationRules(int $id = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:30',
                'min:2',
                'regex:/^[\p{L}\p{N}\s\-\'\.\&]+$/u'
            ],
            'description' => [
                'required',
                'string',
                'max:400',
                'min:10',
                'regex:/^[\p{L}\p{N}\s\-\'\.\,\!\?\(\)\&\:\;\"\"\'\']+$/u'
            ],
            'promoter_id' => [
                'required',
                'integer',
                'exists:promoters,id'
            ],
            'wallet_balance' => [
                'nullable',
                'integer',
                'min:0',
                'max:999999999999'
            ],
            'investment_goal' => [
                'nullable',
                'integer',
                'min:0',
                'max:999999999999'
            ],
        ];
    }

    /**
     * Get the promoter that owns the project.
     *
     * @return BelongsTo
     */
    public function promoter(): BelongsTo
    {
        return $this->belongsTo(Promoter::class);
    }

    /**
     * Get the amortizations for the project.
     *
     * @return HasMany
     */
    public function amortizations(): HasMany
    {
        return $this->hasMany(Amortization::class);
    }

    /**
     * Scope a query to filter by promoter.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $promoterId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPromoter($query, int $promoterId)
    {
        return $query->where('promoter_id', $promoterId);
    }

    /**
     * Scope a query to filter by minimum wallet balance.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $minBalance
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithMinBalance($query, int $minBalance)
    {
        return $query->where('wallet_balance', '>=', $minBalance);
    }

    /**
     * Scope a query to filter projects with sufficient funds.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithSufficientFunds($query)
    {
        return $query->whereRaw('wallet_balance >= (SELECT COALESCE(SUM(amount), 0) FROM amortizations WHERE project_id = projects.id AND state = "pending")');
    }

    /**
     * Get the total pending amortization amount.
     *
     * @return int
     */
    public function getTotalPendingAmortizationsAttribute(): int
    {
        return $this->amortizations()->where('state', 'pending')->sum('amount');
    }

    /**
     * Get the total paid amortization amount.
     *
     * @return int
     */
    public function getTotalPaidAmortizationsAttribute(): int
    {
        return $this->amortizations()->where('state', 'paid')->sum('amount');
    }

    /**
     * Get the formatted wallet balance.
     *
     * @return string
     */
    public function getFormattedWalletBalanceAttribute(): string
    {
        return number_format($this->wallet_balance / 100, 2) . ' USD';
    }

    /**
     * Get the investment progress percentage.
     *
     * @return float
     */
    public function getInvestmentProgressAttribute(): float
    {
        if (!$this->investment_goal || $this->investment_goal === 0) {
            return 0.0;
        }
        
        return min(100.0, ($this->getTotalPaidAmortizationsAttribute() / $this->investment_goal) * 100);
    }

    /**
     * Check if project has sufficient funds for amount.
     *
     * @param int $amount
     * @return bool
     */
    public function hasSufficientFunds(int $amount): bool
    {
        return $this->wallet_balance >= $amount;
    }

    /**
     * Deduct amount from wallet balance.
     *
     * @param int $amount
     * @return bool
     */
    public function deductBalance(int $amount): bool
    {
        if (!$this->hasSufficientFunds($amount)) {
            return false;
        }
        
        $this->wallet_balance -= $amount;
        return $this->save();
    }

    /**
     * Add amount to wallet balance.
     *
     * @param int $amount
     * @return bool
     */
    public function addBalance(int $amount): bool
    {
        $this->wallet_balance += $amount;
        return $this->save();
    }

    /**
     * Check if investment goal is reached.
     *
     * @return bool
     */
    public function isInvestmentGoalReached(): bool
    {
        return $this->investment_goal > 0 
               && $this->getTotalPaidAmortizationsAttribute() >= $this->investment_goal;
    }
}
