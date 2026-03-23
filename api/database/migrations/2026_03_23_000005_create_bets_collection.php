<?php

use Illuminate\Database\Migrations\Migration;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        if (Schema::connection('mongodb')->hasCollection('bets')) {
            Schema::connection('mongodb')->drop('bets');
        }

        Schema::connection('mongodb')->create('bets', function (Blueprint $collection) {
            $collection->index('user_id');
            $collection->index('match_id');
            $collection->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('bets');
    }
};
