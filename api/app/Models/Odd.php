<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Odd extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'odds';

    protected $fillable = [
        'match_id',
        'home_win',
        'draw',
        'away_win',
        'bookmaker',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'home_win' => 'float',
            'draw'     => 'float',
            'away_win' => 'float',
        ];
    }

    public function match()
    {
        return $this->belongsTo(SportMatch::class, 'match_id');
    }
}
