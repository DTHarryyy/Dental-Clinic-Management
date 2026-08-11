<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TABLES = [
        'migrations', 'users', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks',
        'jobs', 'job_batches', 'failed_jobs', 'services', 'patients', 'appointments',
        'dental_records', 'invoices', 'invoice_items', 'clinic_settings', 'payments',
        'billing_email_deliveries',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE INDEX IF NOT EXISTS payments_received_by_index ON payments(received_by)');

        foreach (self::TABLES as $table) {
            $policy = $table.'_backend_only';
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("DROP POLICY IF EXISTS {$policy} ON {$table}");
            DB::statement("CREATE POLICY {$policy} ON {$table} AS RESTRICTIVE FOR ALL TO anon, authenticated USING (false) WITH CHECK (false)");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::TABLES as $table) {
            DB::statement("DROP POLICY IF EXISTS {$table}_backend_only ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }

        DB::statement('DROP INDEX IF EXISTS payments_received_by_index');
    }
};
