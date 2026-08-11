<?php

namespace App\Services;

/**
 * Backwards-compatible application email client. Despite the historical name,
 * delivery now goes through the protected Supabase Edge Function, not Resend directly.
 */
class ResendAppointmentClient
{
    public function __construct(private SupabaseEmailGateway $gateway) {}

    public function send(array $parameters, array $options): ?string
    {
        return $this->gateway->send([
            'to' => is_array($parameters['to']) ? $parameters['to'][0] : $parameters['to'],
            'subject' => $parameters['subject'],
            'html' => $parameters['html'],
            'text' => $parameters['text'],
            'reply_to' => isset($parameters['reply_to']) ? (is_array($parameters['reply_to']) ? $parameters['reply_to'][0] : $parameters['reply_to']) : null,
            'attachments' => $parameters['attachments'] ?? [],
            'idempotency_key' => $options['idempotency_key'],
        ]);
    }
}
