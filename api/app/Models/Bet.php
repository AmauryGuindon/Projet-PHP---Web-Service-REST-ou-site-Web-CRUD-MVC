<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bet extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'bets';

    protected $fillable = [
        'user_id',
        'match_id',
        'amount',
        'predicted_outcome',
        'odds_value',
        'potential_gain',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount'         => 'float',
            'odds_value'     => 'float',
            'potential_gain' => 'float',
        ];
    }

    public function match()
    {
        return $this->belongsTo(SportMatch::class, 'match_id');
    }
}
