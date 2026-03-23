<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Team extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'teams';

    protected $fillable = [
        'sport_id',
        'name',
        'short_name',
        'country',
        'logo_url',
    ];

    public function sport()
    {
        return $this->belongsTo(Sport::class, 'sport_id');
    }

    public function homeMatches()
    {
        return $this->hasMany(SportMatch::class, 'home_team_id');
    }

    public function awayMatches()
    {
        return $this->hasMany(SportMatch::class, 'away_team_id');
    }
}
