<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sport extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'sports';

    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function teams()
    {
        return $this->hasMany(Team::class, 'sport_id');
    }

    public function matches()
    {
        return $this->hasMany(SportMatch::class, 'sport_id');
    }
}
