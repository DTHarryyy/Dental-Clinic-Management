<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_email_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->uuid('idempotency_key')->unique();
            $table->string('document_type', 20); // invoice | receipt
            $table->string('recipient');
            $table->string('trigger', 20); // automatic | manual
            $table->string('status', 20)->default('queued'); // queued | sent | failed
            $table->string('provider_message_id')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'document_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_email_deliveries');
    }
};
