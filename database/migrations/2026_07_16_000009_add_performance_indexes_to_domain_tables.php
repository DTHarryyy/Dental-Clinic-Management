<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->index('patient_id');
            $table->index('dentist_id');
            $table->index('status');
            $table->index('appointment_date');
        });

        Schema::table('dental_records', function (Blueprint $table) {
            $table->index('patient_id');
            $table->index('dentist_id');
            $table->index('treatment_date');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index('patient_id');
            $table->index('dental_record_id');
            $table->index('payment_status');
            $table->index('invoice_date');
            $table->index('due_date');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->index('invoice_id');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['patient_id']);
            $table->dropIndex(['dentist_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['appointment_date']);
        });

        Schema::table('dental_records', function (Blueprint $table) {
            $table->dropIndex(['patient_id']);
            $table->dropIndex(['dentist_id']);
            $table->dropIndex(['treatment_date']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['patient_id']);
            $table->dropIndex(['dental_record_id']);
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['invoice_date']);
            $table->dropIndex(['due_date']);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex(['invoice_id']);
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['status']);
        });
    }
};
