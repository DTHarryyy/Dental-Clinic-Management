<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('status', 20)->default('verified')->after('method');
            $table->string('proof_path')->nullable()->after('reference');
            $table->foreignId('submitted_by')->nullable()->after('received_by')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('submitted_by');
            $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('notes');

            $table->index(['status', 'created_at']);
            $table->index(['invoice_id', 'status']);
        });

        // Every payment recorded before this migration was entered by staff at the
        // counter (there was no patient-submission path yet), so it is verified by
        // definition - stamp the audit trail from the fields that already exist.
        DB::table('payments')->update([
            'status' => 'verified',
            'verified_at' => DB::raw('paid_at'),
            'verified_by' => DB::raw('received_by'),
        ]);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropForeign(['verified_by']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['invoice_id', 'status']);
            $table->dropColumn(['status', 'proof_path', 'submitted_by', 'verified_at', 'verified_by', 'rejection_reason']);
        });
    }
};
