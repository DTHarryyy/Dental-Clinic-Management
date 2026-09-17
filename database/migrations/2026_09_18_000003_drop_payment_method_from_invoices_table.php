<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Denormalized and stale by design - it only ever recorded the *first*
        // payment's method (BillingController::store()) and was never updated by
        // recordPayment(). Invoice::getPaymentMethodsSummaryAttribute() derives the
        // same information correctly (and for every method used, not just the first).
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('payment_status');
        });
    }
};
