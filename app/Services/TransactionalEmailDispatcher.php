<?php

namespace App\Services;

use App\Jobs\SendTransactionalEmail;
use App\Models\EmailDelivery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

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
}
