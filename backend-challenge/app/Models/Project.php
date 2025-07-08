<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'wallet_balance',
        'investment_goal',
        'promoter_id'
    ];

    public function promoter()
    {
        return $this->belongsTo(Promoter::class);
    }

    public function amortizations()
    {
        return $this->hasMany(Amortization::class);
    }
}
