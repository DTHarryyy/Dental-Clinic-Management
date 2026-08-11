<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('status');
        });

        Schema::create('email_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50);
            $table->nullableMorphs('related');
            $table->string('recipient');
            $table->string('status', 20)->default('pending');
            $table->uuid('idempotency_key')->unique();
            $table->string('provider_message_id')->nullable();
            $table->text('error')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['event_type', 'status']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE email_deliveries ENABLE ROW LEVEL SECURITY');
            DB::statement('CREATE POLICY email_deliveries_backend_only ON email_deliveries AS RESTRICTIVE FOR ALL TO anon, authenticated USING (false) WITH CHECK (false)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP POLICY IF EXISTS email_deliveries_backend_only ON email_deliveries');
        }
        Schema::dropIfExists('email_deliveries');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('must_change_password'));
    }
};
