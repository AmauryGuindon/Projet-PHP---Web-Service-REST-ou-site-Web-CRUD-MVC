<?php

use Illuminate\Database\Migrations\Migration;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        if (Schema::connection('mongodb')->hasCollection('teams')) {
            Schema::connection('mongodb')->drop('teams');
        }

        Schema::connection('mongodb')->create('teams', function (Blueprint $collection) {
            $collection->index('sport_id');
            $collection->index('name');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('teams');
    }
};
