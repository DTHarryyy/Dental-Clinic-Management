<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $partialInvoiceIds = DB::table('invoices')->where('payment_status', 'partial')->pluck('id');
        if ($partialInvoiceIds->isNotEmpty()) {
            Log::warning('Partial invoices require payment reconciliation after the payments migration.', [
                'invoice_ids' => $partialInvoiceIds->all(),
            ]);
        }

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('method', 50);
            $table->string('reference')->nullable();
            $table->timestamp('paid_at');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'paid_at']);
        });

        DB::table('invoices')
            ->where('payment_status', 'paid')
            ->orderBy('id')
            ->each(function ($invoice) {
                DB::table('payments')->insert([
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->total,
                    'method' => $invoice->payment_method ?: 'Legacy payment',
                    'reference' => 'Migrated from existing paid invoice',
                    'paid_at' => $invoice->updated_at ?? $invoice->created_at ?? now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
