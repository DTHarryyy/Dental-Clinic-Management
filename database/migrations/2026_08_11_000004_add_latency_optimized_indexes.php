<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->index(['status', 'appointment_date', 'appointment_time'], 'appointments_status_date_time_idx');
            $table->index(['patient_id', 'appointment_date'], 'appointments_patient_date_idx');
        });
        Schema::table('dental_records', fn (Blueprint $table) => $table->index(['patient_id', 'treatment_date'], 'records_patient_date_idx'));
        Schema::table('invoices', function (Blueprint $table): void {
            $table->index(['patient_id', 'invoice_date'], 'invoices_patient_date_idx');
            $table->index(['payment_status', 'due_date'], 'invoices_status_due_idx');
        });
        Schema::table('payments', fn (Blueprint $table) => $table->index('paid_at', 'payments_paid_at_idx'));

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
            DB::statement("CREATE INDEX patients_search_trgm_idx ON patients USING gin ((lower(first_name || ' ' || last_name || ' ' || coalesce(email, ''))) gin_trgm_ops)");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS patients_search_trgm_idx');
        }

        Schema::table('payments', fn (Blueprint $table) => $table->dropIndex('payments_paid_at_idx'));
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex('invoices_patient_date_idx');
            $table->dropIndex('invoices_status_due_idx');
        });
        Schema::table('dental_records', fn (Blueprint $table) => $table->dropIndex('records_patient_date_idx'));
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex('appointments_status_date_time_idx');
            $table->dropIndex('appointments_patient_date_idx');
        });
    }
};
