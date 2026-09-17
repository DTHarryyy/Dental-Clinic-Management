<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ServiceSeeder::run() is idempotent only by convention (firstOrCreate on name) —
     * without a real unique constraint, two concurrent seed runs (or a race between the
     * seeder and an admin adding the same service in Settings) can duplicate the catalog.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }
};
