<?php

use Illuminate\Database\Migrations\Migration;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        if (Schema::connection('mongodb')->hasCollection('sports')) {
            Schema::connection('mongodb')->drop('sports');
        }

        Schema::connection('mongodb')->create('sports', function (Blueprint $collection) {
            $collection->index('slug', null, null, ['unique' => true]);
            $collection->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('sports');
    }
};
