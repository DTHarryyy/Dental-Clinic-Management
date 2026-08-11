<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SupabaseEmailGateway
{
    public function send(array $payload): ?string
    {
        if (config('mail.default') !== 'resend') {
            return null;
        }

        $url = rtrim((string) config('services.supabase.url'), '/');
        $key = (string) config('services.supabase.service_role_key');

        if ($url === '' || $key === '') {
            throw new RuntimeException('The Supabase email gateway is not configured.');
        }

        try {
            $response = Http::timeout(30)->withHeaders([
                'apikey' => $key,
                'Authorization' => "Bearer {$key}",
                'Content-Type' => 'application/json',
            ])->post($url.'/functions/v1/'.config('services.supabase.email_function'), $payload);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Could not connect to the email delivery service.', previous: $e);
        }

        if ($response->failed()) {
            throw new RuntimeException('Email delivery service failed with status '.$response->status().'.');
        }

        $id = $response->json('id');
        if (! is_string($id) || $id === '') {
            throw new RuntimeException('Email delivery service returned an invalid response.');
        }

        return $id;
    }
}
