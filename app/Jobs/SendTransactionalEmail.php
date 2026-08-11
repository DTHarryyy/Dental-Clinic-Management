<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\EmailDelivery;
use App\Models\User;
use App\Services\SupabaseEmailGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SendTransactionalEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $deliveryId, public ?string $encryptedSecrets = null) {}

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping("transactional-email-{$this->deliveryId}"))->releaseAfter(30)->expireAfter(300)];
    }

    public function handle(SupabaseEmailGateway $gateway): void
    {
        $delivery = EmailDelivery::find($this->deliveryId);
        if (! $delivery || $delivery->status === 'sent') {
            return;
        }

        $delivery->increment('attempts');

        try {
            $message = $this->message($delivery);
            if (config('mail.default') !== 'resend') {
                Mail::html($message['html'], fn ($mail) => $mail->to($delivery->recipient)->subject($message['subject']));
                $providerId = null;
            } else {
                $providerId = $gateway->send([
                    'to' => $delivery->recipient,
                    'subject' => $message['subject'],
                    'html' => $message['html'],
                    'text' => $message['text'],
                    'reply_to' => $message['reply_to'] ?? null,
                    'idempotency_key' => "transactional-delivery/{$delivery->idempotency_key}",
                ]);
            }
            $delivery->update(['status' => 'sent', 'provider_message_id' => $providerId, 'error' => null, 'sent_at' => now()]);
        } catch (Throwable $e) {
            $delivery->update(['status' => 'failed', 'error' => Str::limit($e->getMessage(), 2000, '…')]);
            throw $e;
        }
    }

    public function failed(?Throwable $e): void
    {
        if ($e) {
            EmailDelivery::whereKey($this->deliveryId)->update(['status' => 'failed', 'error' => Str::limit($e->getMessage(), 2000, '…')]);
        }
    }

    private function message(EmailDelivery $delivery): array
    {
        $clinic = ClinicSetting::current();
        $secrets = $this->encryptedSecrets ? json_decode(Crypt::decryptString($this->encryptedSecrets), true, flags: JSON_THROW_ON_ERROR) : [];
        $entity = match ($delivery->event_type) {
            'booking_received', 'appointment_cancelled' => Appointment::with(['dentist', 'serviceItems', 'rescheduledAppointment.serviceItems'])->findOrFail($delivery->related_id),
            'staff_credentials' => User::findOrFail($delivery->related_id),
            default => null,
        };
        $data = ['event' => $delivery->event_type, 'entity' => $entity, 'clinic' => $clinic, 'secrets' => $secrets];
        $subject = match ($delivery->event_type) {
            'booking_received' => 'Appointment Request Received — Aquilizan Dental Clinic',
            'appointment_cancelled' => 'Appointment Cancelled — Aquilizan Dental Clinic',
            'staff_credentials' => 'Your Aquilizan Dental Clinic Staff Account',
            'password_reset' => 'Reset Your Aquilizan Dental Clinic Password',
            default => throw new \RuntimeException('Unsupported transactional email event.'),
        };

        return [
            'subject' => $subject,
            'html' => view('mail.transactional.message', $data)->render(),
            'text' => view('mail.transactional.message-text', $data)->render(),
            'reply_to' => filled($clinic->email) ? $clinic->email : null,
        ];
    }
}
