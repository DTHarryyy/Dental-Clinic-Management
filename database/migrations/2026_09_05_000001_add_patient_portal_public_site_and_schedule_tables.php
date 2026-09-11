<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin', 'dentist', 'receptionist', 'patient'))");
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('patient_id')->nullable()->after('supabase_uid')->constrained()->nullOnDelete();
            $table->unique('patient_id', 'users_patient_id_unique');
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->foreignId('requested_by_user_id')->nullable()->after('dentist_id')->constrained('users')->nullOnDelete();
            $table->index(['requested_by_user_id', 'status'], 'appointments_requester_status_idx');
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->text('public_description')->nullable()->after('duration_minutes');
            $table->string('public_image_path')->nullable()->after('public_description');
            $table->unsignedSmallInteger('public_sort_order')->default(0)->after('public_image_path');
            $table->boolean('show_public_price')->default(true)->after('public_sort_order');
        });

        Schema::table('clinic_settings', function (Blueprint $table): void {
            $table->unsignedSmallInteger('booking_lead_minutes')->default(120)->after('website');
            $table->unsignedSmallInteger('booking_horizon_days')->default(90)->after('booking_lead_minutes');
            $table->unsignedSmallInteger('slot_interval_minutes')->default(30)->after('booking_horizon_days');
        });

        Schema::table('dental_records', function (Blueprint $table): void {
            $table->text('patient_summary')->nullable()->after('clinical_notes');
            $table->text('aftercare_instructions')->nullable()->after('patient_summary');
            $table->timestamp('published_at')->nullable()->after('aftercare_instructions');
            $table->foreignId('published_by_user_id')->nullable()->after('published_at')->constrained('users')->nullOnDelete();
            $table->index(['patient_id', 'published_at'], 'dental_records_patient_published_idx');
        });

        Schema::create('patient_consents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('version', 80);
            $table->timestamp('accepted_at');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'type']);
        });

        Schema::create('patient_account_link_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('normalized_email')->index();
            $table->unsignedSmallInteger('candidate_count')->default(0);
            $table->string('status', 30)->default('pending')->index();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('selected_patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'status'], 'patient_account_link_user_status_unique');
        });

        Schema::create('clinic_business_hours', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('day_of_week')->unique();
            $table->boolean('is_open')->default(true);
            $table->time('morning_opens_at')->nullable();
            $table->time('morning_closes_at')->nullable();
            $table->time('afternoon_opens_at')->nullable();
            $table->time('afternoon_closes_at')->nullable();
            $table->timestamps();
        });

        Schema::create('clinic_closures', function (Blueprint $table): void {
            $table->id();
            $table->date('closure_date')->index();
            $table->boolean('is_full_day')->default(true);
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->string('reason');
            $table->timestamps();
        });

        Schema::create('public_site_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('hero_eyebrow')->nullable();
            $table->string('hero_title')->default('Aquilizan Dental Clinic');
            $table->text('hero_subtitle')->nullable();
            $table->string('hero_image_path')->nullable();
            $table->text('about_heading')->nullable();
            $table->text('about_body')->nullable();
            $table->json('benefits')->nullable();
            $table->string('map_embed_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_image_path')->nullable();
            $table->longText('privacy_policy')->nullable();
            $table->longText('terms')->nullable();
            $table->string('privacy_policy_version')->default('privacy-v1');
            $table->string('terms_version')->default('terms-v1');
            $table->timestamps();
        });

        Schema::create('public_team_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('specialties')->nullable();
            $table->text('biography')->nullable();
            $table->string('photo_path')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->index(['is_published', 'display_order']);
        });

        Schema::create('faqs', function (Blueprint $table): void {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'display_order']);
        });

        Schema::create('appointment_change_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->text('reason');
            $table->timestamp('original_start_at')->nullable();
            $table->timestamp('original_end_at')->nullable();
            $table->timestamp('proposed_start_at')->nullable();
            $table->timestamp('proposed_end_at')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->unique(['appointment_id', 'status'], 'appointment_change_pending_unique');
            $table->index(['patient_user_id', 'status']);
        });

        DB::table('clinic_business_hours')->insert(collect(range(0, 6))->map(fn (int $day) => [
            'day_of_week' => $day,
            'is_open' => true,
            'morning_opens_at' => '08:00:00',
            'morning_closes_at' => '12:00:00',
            'afternoon_opens_at' => '13:00:00',
            'afternoon_closes_at' => '17:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_change_requests');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('public_team_profiles');
        Schema::dropIfExists('public_site_settings');
        Schema::dropIfExists('clinic_closures');
        Schema::dropIfExists('clinic_business_hours');
        Schema::dropIfExists('patient_account_link_requests');
        Schema::dropIfExists('patient_consents');

        Schema::table('dental_records', function (Blueprint $table): void {
            $table->dropIndex('dental_records_patient_published_idx');
            $table->dropConstrainedForeignId('published_by_user_id');
            $table->dropColumn(['patient_summary', 'aftercare_instructions', 'published_at']);
        });

        Schema::table('clinic_settings', function (Blueprint $table): void {
            $table->dropColumn(['booking_lead_minutes', 'booking_horizon_days', 'slot_interval_minutes']);
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->dropColumn(['public_description', 'public_image_path', 'public_sort_order', 'show_public_price']);
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex('appointments_requester_status_idx');
            $table->dropConstrainedForeignId('requested_by_user_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_patient_id_unique');
            $table->dropConstrainedForeignId('patient_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin', 'dentist', 'receptionist'))");
        }
    }
};
