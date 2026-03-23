<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SportMatch extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'matches';

    protected $fillable = [
        'sport_id',
        'home_team_id',
        'away_team_id',
        'starts_at',
        'status',
        'home_score',
        'away_score',
    ];

    protected function casts(): array
    {
        return [
            'starts_at'  => 'datetime',
            'home_score' => 'integer',
            'away_score' => 'integer',
        ];
    }

    public function sport()
    {
        return $this->belongsTo(Sport::class, 'sport_id');
    }

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }
}
