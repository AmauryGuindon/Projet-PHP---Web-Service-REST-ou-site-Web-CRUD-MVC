<?php

use Illuminate\Database\Migrations\Migration;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        if (Schema::connection('mongodb')->hasCollection('odds')) {
            Schema::connection('mongodb')->drop('odds');
        }

        Schema::connection('mongodb')->create('odds', function (Blueprint $collection) {
            $collection->index('match_id');
            $collection->index('bookmaker');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('odds');
    }
};
