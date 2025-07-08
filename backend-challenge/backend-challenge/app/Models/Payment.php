<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class Payment extends Model
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
        'user_id',
        'amortization_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'integer',
        'state' => 'string',
        'user_id' => 'integer',
        'amortization_id' => 'integer',
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
            'user_id' => [
                'required',
                'integer',
                'exists:users,id'
            ],
            'amortization_id' => [
                'required',
                'integer',
                'exists:amortizations,id'
            ],
        ];
    }

    /**
     * Get the amortization that owns the payment.
     *
     * @return BelongsTo
     */
    public function amortization(): BelongsTo
    {
        return $this->belongsTo(Amortization::class);
    }

    /**
     * Get the user that owns the payment.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include pending payments.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('state', 'pending');
    }

    /**
     * Scope a query to only include paid payments.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        return $query->where('state', 'paid');
    }

    /**
     * Scope a query to filter by amount range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $min
     * @param int $max
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAmountRange($query, int $min, int $max)
    {
        return $query->whereBetween('amount', [$min, $max]);
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
     * Check if the payment is delayed (amortization is past due).
     *
     * @return bool
     */
    public function getIsDelayedAttribute(): bool
    {
        return $this->amortization 
               && $this->amortization->schedule_date < now() 
               && $this->state === 'pending';
    }

    /**
     * Get the formatted amount with currency.
     *
     * @return string
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount / 100, 2) . ' USD'; // Assuming amount is in cents
    }

    /**
     * Mark the payment as paid.
     *
     * @return bool
     */
    public function markAsPaid(): bool
    {
        $this->state = 'paid';
        return $this->save();
    }

    /**
     * Check if the payment can be processed.
     *
     * @return bool
     */
    public function canBeProcessed(): bool
    {
        return $this->state === 'pending' 
               && $this->amortization 
               && $this->amortization->canBeProcessed();
    }

    /**
     * Get the project through the amortization.
     *
     * @return Project|null
     */
    public function getProjectAttribute(): ?Project
    {
        return $this->amortization?->project;
    }
}
