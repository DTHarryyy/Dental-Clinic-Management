<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dental_records', function (Blueprint $table) {
            // Nullable: walk-in treatments are recorded without an appointment.
            // Unique: an appointment can only ever produce one record, and both Postgres
            // and SQLite allow repeated NULLs, so walk-ins are unaffected by the constraint.
            $table->foreignId('appointment_id')->nullable()->unique()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dental_records', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->dropUnique(['appointment_id']);
            $table->dropColumn('appointment_id');
        });
    }
};
