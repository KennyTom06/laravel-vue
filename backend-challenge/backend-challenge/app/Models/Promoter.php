<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promoter extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
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
        $uniqueRule = $id ? "unique:promoters,user_id,{$id}" : 'unique:promoters,user_id';
        
        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                $uniqueRule
            ],
        ];
    }

    /**
     * Get the user that owns the promoter.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the projects for the promoter.
     *
     * @return HasMany
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get the amortizations through projects.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function amortizations()
    {
        return $this->hasManyThrough(Amortization::class, Project::class);
    }

    /**
     * Scope a query to filter by user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to include promoters with active projects.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithActiveProjects($query)
    {
        return $query->whereHas('projects');
    }

    /**
     * Get the total number of projects.
     *
     * @return int
     */
    public function getTotalProjectsAttribute(): int
    {
        return $this->projects()->count();
    }

    /**
     * Get the total wallet balance across all projects.
     *
     * @return int
     */
    public function getTotalWalletBalanceAttribute(): int
    {
        return $this->projects()->sum('wallet_balance');
    }

    /**
     * Get the total pending amortizations amount.
     *
     * @return int
     */
    public function getTotalPendingAmortizationsAttribute(): int
    {
        return $this->amortizations()->where('state', 'pending')->sum('amount');
    }

    /**
     * Get the total paid amortizations amount.
     *
     * @return int
     */
    public function getTotalPaidAmortizationsAttribute(): int
    {
        return $this->amortizations()->where('state', 'paid')->sum('amount');
    }

    /**
     * Get the promoter's performance score.
     *
     * @return float
     */
    public function getPerformanceScoreAttribute(): float
    {
        $totalAmortizations = $this->amortizations()->count();
        
        if ($totalAmortizations === 0) {
            return 0.0;
        }
        
        $paidAmortizations = $this->amortizations()->where('state', 'paid')->count();
        
        return ($paidAmortizations / $totalAmortizations) * 100;
    }

    /**
     * Check if promoter has any overdue amortizations.
     *
     * @return bool
     */
    public function hasOverdueAmortizations(): bool
    {
        return $this->amortizations()
                    ->where('state', 'pending')
                    ->where('schedule_date', '<', now())
                    ->exists();
    }

    /**
     * Get the promoter's email through user relationship.
     *
     * @return string|null
     */
    public function getEmailAttribute(): ?string
    {
        return $this->user?->email;
    }

    /**
     * Get the promoter's name through user relationship.
     *
     * @return string|null
     */
    public function getNameAttribute(): ?string
    {
        return $this->user?->name;
    }
}
