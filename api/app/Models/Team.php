<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'sport_id',
        'name',
        'short_name',
        'country',
    ];

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function homeMatches(): HasMany
    {
        return $this->hasMany(SportMatch::class, 'home_team_id');
    }

    public function awayMatches(): HasMany
    {
        return $this->hasMany(SportMatch::class, 'away_team_id');
    }
}
