<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dental_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dentist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('treatment_date');
            $table->string('procedure');
            $table->string('tooth_area')->nullable();
            $table->date('next_appointment_date')->nullable();
            $table->text('clinical_notes')->nullable();
            $table->text('prescription')->nullable();
            $table->decimal('treatment_fee', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_records');
    }
};
