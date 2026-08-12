<?php

namespace App\Services;

use App\Jobs\SendTransactionalEmail;
use App\Models\EmailDelivery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Throwable;

class TransactionalEmailDispatcher
{
    public function dispatch(string $event, string $recipient, ?Model $related = null, array $secrets = []): EmailDelivery
    {
        $delivery = EmailDelivery::create([
            'event_type' => $event,
            'related_type' => $related?->getMorphClass(),
            'related_id' => $related?->getKey(),
            'recipient' => $recipient,
            'idempotency_key' => (string) Str::uuid(),
        ]);

        SendTransactionalEmail::dispatch($delivery->id, $secrets ? Crypt::encryptString(json_encode($secrets, JSON_THROW_ON_ERROR)) : null)->afterCommit();

        return $delivery;
    }

    /**
     * Queue one logical delivery, or re-queue its existing record after a terminal failure.
     */
    public function dispatchOnce(string $event, string $recipient, Model $related, string $idempotencyKey): bool
    {
        $delivery = EmailDelivery::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'event_type' => $event,
                'related_type' => $related->getMorphClass(),
                'related_id' => $related->getKey(),
                'recipient' => $recipient,
            ],
        );

        if (! $delivery->wasRecentlyCreated && $delivery->status !== 'failed') {
            return false;
        }

        if (! $delivery->wasRecentlyCreated) {
            $delivery->update(['status' => 'pending', 'error' => null]);
        }

        try {
            SendTransactionalEmail::dispatch($delivery->id)->afterCommit();
        } catch (Throwable $exception) {
            $delivery->update([
                'status' => 'failed',
                'error' => Str::limit($exception->getMessage(), 2000, '…'),
            ]);

            throw $exception;
        }

        return true;
    }
}
