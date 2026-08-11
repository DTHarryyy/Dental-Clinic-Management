<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('confirmation_email_sent_at')->nullable()->after('status');
            $table->string('confirmation_email_message_id')->nullable()->after('confirmation_email_sent_at');
            $table->text('confirmation_email_error')->nullable()->after('confirmation_email_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'confirmation_email_sent_at',
                'confirmation_email_message_id',
                'confirmation_email_error',
            ]);
        });
    }
};
