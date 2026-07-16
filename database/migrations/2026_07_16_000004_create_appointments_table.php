<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dentist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('full_name');
            $table->string('contact_number');
            $table->string('email')->nullable();
            $table->date('appointment_date');
            $table->string('appointment_time')->nullable();
            $table->string('service');
            $table->text('concern')->nullable();
            $table->string('status')->default('pending'); // pending | confirmed | completed | cancelled
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
