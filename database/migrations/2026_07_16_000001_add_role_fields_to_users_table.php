<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('receptionist')->after('email'); // admin | dentist | receptionist
            $table->string('phone')->nullable()->after('role');
            $table->string('license_no')->nullable()->after('phone');
            $table->string('status')->default('active')->after('license_no'); // active | inactive
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'license_no', 'status']);
        });
    }
};
