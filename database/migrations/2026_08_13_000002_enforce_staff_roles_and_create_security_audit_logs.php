<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $invalidIds = DB::table('users')
            ->whereNotIn('role', ['admin', 'dentist', 'receptionist'])
            ->orWhereNotIn('status', ['active', 'inactive'])
            ->pluck('id');

        if ($invalidIds->isNotEmpty()) {
            throw new \RuntimeException('Invalid staff role/status values found for user IDs: '.$invalidIds->join(', '));
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin', 'dentist', 'receptionist'))");
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_check CHECK (status IN ('active', 'inactive'))");
        }

        Schema::create('security_audit_logs', function (Blueprint $table): void {
            $table->id();
            // Deliberately not a foreign key: audit rows retain the actor ID snapshot even
            // after the staff account is deleted, and no cascade may mutate this table.
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->string('actor_role')->nullable();
            $table->string('event')->index();
            $table->string('ability')->nullable()->index();
            $table->string('result', 20)->index();
            $table->string('target_type')->nullable();
            $table->string('target_id')->nullable();
            $table->string('route_name')->nullable();
            $table->string('method', 10)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['target_type', 'target_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE security_audit_logs ENABLE ROW LEVEL SECURITY');
            DB::statement('CREATE POLICY security_audit_logs_backend_only ON security_audit_logs AS RESTRICTIVE FOR ALL TO anon, authenticated USING (false) WITH CHECK (false)');
            DB::statement(<<<'SQL'
                CREATE FUNCTION prevent_security_audit_log_mutation() RETURNS trigger AS $$
                BEGIN
                    RAISE EXCEPTION 'security_audit_logs is append-only';
                END;
                $$ LANGUAGE plpgsql
            SQL);
            DB::statement('CREATE TRIGGER security_audit_logs_append_only BEFORE UPDATE OR DELETE ON security_audit_logs FOR EACH ROW EXECUTE FUNCTION prevent_security_audit_log_mutation()');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('security_audit_logs');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP FUNCTION IF EXISTS prevent_security_audit_log_mutation()');
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_status_check');
        }
    }
};
