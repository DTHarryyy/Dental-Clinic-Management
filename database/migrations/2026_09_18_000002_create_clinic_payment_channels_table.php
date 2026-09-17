<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_payment_channels', function (Blueprint $table) {
            $table->id();
            $table->string('method', 50)->unique(); // App\Enums\PaymentMethod value
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('bank_name')->nullable(); // Bank Transfer only
            $table->string('qr_path')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_payment_channels');
    }
};
