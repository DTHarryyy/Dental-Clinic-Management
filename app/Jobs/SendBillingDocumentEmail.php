<?php

namespace App\Jobs;

use App\Models\BillingEmailDelivery;
use App\Services\BillingDocumentSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Str;
use Throwable;

class SendBillingDocumentEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $deliveryId) {}

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping("billing-email-{$this->deliveryId}"))->releaseAfter(30)->expireAfter(300)];
    }

    public function handle(BillingDocumentSender $sender): void
    {
        $delivery = BillingEmailDelivery::with('invoice')->find($this->deliveryId);

        if (! $delivery || $delivery->status === 'sent') {
            return;
        }

        try {
            $messageId = $sender->send($delivery);
            $delivery->update([
                'status' => 'sent',
                'provider_message_id' => $messageId,
                'error' => null,
                'sent_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $delivery->update(['status' => 'failed', 'error' => $this->safeError($exception)]);
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception) {
            BillingEmailDelivery::whereKey($this->deliveryId)->update([
                'status' => 'failed',
                'error' => $this->safeError($exception),
            ]);
        }
    }

    private function safeError(Throwable $exception): string
    {
        $message = $exception->getMessage();

        return Str::limit($message, 2000, '…');
    }
}
