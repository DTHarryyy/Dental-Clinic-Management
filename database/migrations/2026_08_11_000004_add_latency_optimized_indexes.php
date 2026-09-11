<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $this->createPostgresIndexes();

            return;
        }

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

    private function createPostgresIndexes(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS appointments_status_date_time_idx ON appointments (status, appointment_date, appointment_time)');
        DB::statement('CREATE INDEX IF NOT EXISTS appointments_patient_date_idx ON appointments (patient_id, appointment_date)');
        DB::statement('CREATE INDEX IF NOT EXISTS records_patient_date_idx ON dental_records (patient_id, treatment_date)');
        DB::statement('CREATE INDEX IF NOT EXISTS invoices_patient_date_idx ON invoices (patient_id, invoice_date)');
        DB::statement('CREATE INDEX IF NOT EXISTS invoices_status_due_idx ON invoices (payment_status, due_date)');
        DB::statement('CREATE INDEX IF NOT EXISTS payments_paid_at_idx ON payments (paid_at)');

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        $opclass = DB::selectOne(<<<'SQL'
            SELECT n.nspname AS schema_name
            FROM pg_opclass oc
            INNER JOIN pg_am am ON am.oid = oc.opcmethod
            INNER JOIN pg_namespace n ON n.oid = oc.opcnamespace
            WHERE oc.opcname = 'gin_trgm_ops'
              AND am.amname = 'gin'
            LIMIT 1
        SQL);

        if (! $opclass?->schema_name) {
            throw new RuntimeException('The pg_trgm extension is required before creating patients_search_trgm_idx.');
        }

        $schema = $this->quoteIdentifier($opclass->schema_name);

        DB::statement("CREATE INDEX IF NOT EXISTS patients_search_trgm_idx ON patients USING gin ((lower(first_name || ' ' || last_name || ' ' || coalesce(email, ''))) {$schema}.gin_trgm_ops)");
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }
};
