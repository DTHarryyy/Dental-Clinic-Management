<?php

namespace Tests\Feature;

use App\Jobs\SendTransactionalEmail;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use App\Services\SupabaseEmailGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TransactionalEmailFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_booking_creates_an_audited_received_email_after_commit(): void
    {
        Queue::fake();
        Service::create(['name' => 'Cleaning', 'price' => 500, 'duration' => 30, 'status' => 'active']);

        $this->post(route('public.book.store'), [
            'full_name' => 'Patient One', 'email' => 'patient@example.test',
            'appointment_date' => now()->addDay()->toDateString(), 'service' => 'Cleaning',
        ])->assertRedirect(route('public.book.success'));

        $this->assertDatabaseHas('email_deliveries', ['event_type' => 'booking_received', 'recipient' => 'patient@example.test', 'status' => 'pending']);
        Queue::assertPushed(SendTransactionalEmail::class, 1);
    }

    public function test_cancelling_an_appointment_queues_one_cancellation_message(): void
    {
        Queue::fake();
        $staff = User::factory()->create(['role' => 'admin']);
        $appointment = Appointment::factory()->create(['status' => 'confirmed', 'email' => 'patient@example.test']);

        $this->actingAs($staff)->post(route('appointments.status', $appointment), ['status' => 'cancelled', 'cancellation_reason' => 'Clinic unavailable.'])->assertRedirect();

        $this->assertDatabaseHas('email_deliveries', ['event_type' => 'appointment_cancelled', 'related_id' => $appointment->id]);
        Queue::assertPushed(SendTransactionalEmail::class, 1);
    }

    public function test_forgot_password_is_non_enumerating_and_stores_only_a_hashed_token(): void
    {
        Queue::fake();
        User::factory()->create(['email' => 'staff@example.test', 'status' => 'active', 'supabase_uid' => 'uid-1']);

        $known = $this->post(route('password.email'), ['email' => 'staff@example.test']);
        $unknown = $this->post(route('password.email'), ['email' => 'missing@example.test']);
        $known->assertSessionHas('status', $unknown->getSession()->get('status'));

        $row = DB::table('password_reset_tokens')->where('email', 'staff@example.test')->first();
        $this->assertNotNull($row);
        $this->assertTrue(Hash::needsRehash($row->token) === false);
        $this->assertDatabaseCount('email_deliveries', 1);
    }

    public function test_gateway_uses_the_service_role_and_returns_provider_id(): void
    {
        config()->set('mail.default', 'resend');
        config()->set('services.supabase.url', 'https://project.supabase.co');
        config()->set('services.supabase.service_role_key', 'server-secret');
        Http::fake(['*/functions/v1/send-transactional-email' => Http::response(['id' => 'message-1'])]);

        $id = app(SupabaseEmailGateway::class)->send([
            'to' => 'patient@example.test', 'subject' => 'Test', 'html' => '<p>Test</p>',
            'text' => 'Test', 'idempotency_key' => 'unique-key',
        ]);

        $this->assertSame('message-1', $id);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer server-secret'));
    }
}
