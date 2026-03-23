<?php

use Illuminate\Database\Migrations\Migration;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        if (Schema::connection('mongodb')->hasCollection('matches')) {
            Schema::connection('mongodb')->drop('matches');
        }

        Schema::connection('mongodb')->create('matches', function (Blueprint $collection) {
            $collection->index(['sport_id', 'starts_at']);
            $collection->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('matches');
    }
};
