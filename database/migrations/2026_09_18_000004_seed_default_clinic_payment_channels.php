<?php

use App\Enums\PaymentMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PhilHealth was removed from PaymentMethod - drop any channel row for it so it
        // can never resurface as an "active" method or fail to hydrate the enum cast.
        DB::table('clinic_payment_channels')->where('method', 'PhilHealth')->delete();

        // Every remaining method is offered ("active") by default unless an admin later
        // disables it from Settings -> Payment Channels. insertOrIgnore respects the
        // unique index on `method`, so a channel an admin already configured is untouched.
        $now = now();
        DB::table('clinic_payment_channels')->insertOrIgnore(
            collect(PaymentMethod::cases())->map(fn (PaymentMethod $method) => [
                'method' => $method->value,
                'is_enabled' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );
    }

    public function down(): void
    {
        // Not reversible in a meaningful way - the pre-migration state (which rows
        // existed) is not recorded anywhere.
    }
};
