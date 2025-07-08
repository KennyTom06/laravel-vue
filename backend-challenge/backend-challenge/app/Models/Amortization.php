<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

class Amortization extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'amount',
        'state',
        'schedule_date',
        'project_id',
        'promoter_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'integer',
        'schedule_date' => 'date',
        'state' => 'string',
        'project_id' => 'integer',
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
            'amount' => [
                'required',
                'integer',
                'min:1',
                'max:999999999999'
            ],
            'state' => [
                'required',
                'string',
                Rule::in(['pending', 'paid'])
            ],
            'schedule_date' => [
                'required',
                'date',
                'after_or_equal:today'
            ],
            'project_id' => [
                'required',
                'integer',
                'exists:projects,id'
            ],
            'promoter_id' => [
                'required',
                'integer',
                'exists:promoters,id'
            ],
        ];
    }

    /**
     * Get the project that owns the amortization.
     *
     * @return BelongsTo
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the payments for the amortization.
     *
     * @return HasMany
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the promoter that owns the amortization.
     *
     * @return BelongsTo
     */
    public function promoter(): BelongsTo
    {
        return $this->belongsTo(Promoter::class);
    }

    /**
     * Scope a query to only include pending amortizations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('state', 'pending');
    }

    /**
     * Scope a query to only include paid amortizations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        return $query->where('state', 'paid');
    }

    /**
     * Scope a query to only include delayed amortizations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDelayed($query)
    {
        return $query->where('schedule_date', '<', now())
                    ->where('state', 'pending');
    }

    /**
     * Scope a query to filter by date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('schedule_date', [$startDate, $endDate]);
    }

    /**
     * Get the total amount of payments for this amortization.
     *
     * @return int
     */
    public function getTotalPaymentsAttribute(): int
    {
        return $this->payments()->sum('amount');
    }

    /**
     * Check if the amortization is delayed.
     *
     * @return bool
     */
    public function getIsDelayedAttribute(): bool
    {
        return $this->schedule_date < now() && $this->state === 'pending';
    }

    /**
     * Get the number of days until/since the schedule date.
     *
     * @return int Positive for future dates, negative for past dates
     */
    public function getDaysToScheduleAttribute(): int
    {
        return now()->diffInDays($this->schedule_date, false);
    }

    /**
     * Check if payments match amortization amount.
     *
     * @return bool
     */
    public function paymentsBalanced(): bool
    {
        return $this->getTotalPaymentsAttribute() === $this->amount;
    }

    /**
     * Mark the amortization as paid.
     *
     * @return bool
     */
    public function markAsPaid(): bool
    {
        $this->state = 'paid';
        return $this->save();
    }

    /**
     * Check if the amortization can be processed.
     *
     * @return bool
     */
    public function canBeProcessed(): bool
    {
        return $this->state === 'pending' 
               && $this->schedule_date <= now()
               && $this->project->wallet_balance >= $this->getTotalPaymentsAttribute();
    }
}
