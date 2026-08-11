<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Thin client for Supabase Auth's (GoTrue) REST API. This app authenticates
 * against Supabase instead of checking a local password hash.
 */
class SupabaseAuth
{
    private string $url;

    private string $anonKey;

    private string $serviceRoleKey;

    public function __construct()
    {
        $this->url = rtrim(config('services.supabase.url'), '/');
        $this->anonKey = config('services.supabase.anon_key');
        $this->serviceRoleKey = config('services.supabase.service_role_key');
    }

    /**
     * @return array{ok: bool, message?: string, user?: array}
     */
    public function signIn(string $email, string $password): array
    {
        try {
            $response = $this->client()->post("{$this->url}/auth/v1/token?grant_type=password", [
                'email' => $email,
                'password' => $password,
            ]);
        } catch (ConnectionException) {
            return ['ok' => false, 'message' => 'Could not connect to the authentication service.'];
        }

        if ($response->failed()) {
            return [
                'ok' => false,
                'message' => $response->json('error_description') ?? $response->json('msg') ?? 'Invalid email or password.',
            ];
        }

        return ['ok' => true, 'user' => $response->json('user')];
    }

    /**
     * Admin-provisioned account creation, pre-confirmed (no email step). Used when
     * staff accounts are created from the app's Users management page.
     *
     * @return array{ok: bool, message?: string, user?: array}
     */
    public function adminCreateUser(string $email, string $password): array
    {
        try {
            $response = $this->adminClient()->post("{$this->url}/auth/v1/admin/users", [
                'email' => $email,
                'password' => $password,
                'email_confirm' => true,
            ]);
        } catch (ConnectionException) {
            return ['ok' => false, 'message' => 'Could not connect to Supabase Auth.'];
        }

        if ($response->failed()) {
            return [
                'ok' => false,
                'message' => $response->json('msg') ?? $response->json('error_description') ?? 'Could not create Supabase account.',
            ];
        }

        return ['ok' => true, 'user' => $response->json()];
    }

    /**
     * @param  array<string, mixed>  $attributes  e.g. ['password' => ..., 'email' => ..., 'email_confirm' => true]
     * @return array{ok: bool, message?: string, user?: array}
     */
    public function adminUpdateUser(string $uid, array $attributes): array
    {
        try {
            $response = $this->adminClient()->put("{$this->url}/auth/v1/admin/users/{$uid}", $attributes);
        } catch (ConnectionException) {
            return ['ok' => false, 'message' => 'Could not connect to Supabase Auth.'];
        }

        if ($response->failed()) {
            return [
                'ok' => false,
                'message' => $response->json('msg') ?? $response->json('error_description') ?? 'Could not update Supabase account.',
            ];
        }

        return ['ok' => true, 'user' => $response->json()];
    }

    public function adminDeleteUser(string $uid): bool
    {
        try {
            return $this->adminClient()->delete("{$this->url}/auth/v1/admin/users/{$uid}")->successful();
        } catch (ConnectionException) {
            return false;
        }
    }

    public function adminFindByEmail(string $email): ?array
    {
        try {
            $response = $this->adminClient()->get("{$this->url}/auth/v1/admin/users", [
                'page' => 1,
                'per_page' => 200,
            ]);
        } catch (ConnectionException) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        foreach ($response->json('users') ?? [] as $user) {
            if (($user['email'] ?? null) === $email) {
                return $user;
            }
        }

        return null;
    }

    private function client()
    {
        return Http::withHeaders([
            'apikey' => $this->anonKey,
            'Authorization' => "Bearer {$this->anonKey}",
            'Content-Type' => 'application/json',
        ]);
    }

    private function adminClient()
    {
        return Http::withHeaders([
            'apikey' => $this->serviceRoleKey,
            'Authorization' => "Bearer {$this->serviceRoleKey}",
            'Content-Type' => 'application/json',
        ]);
    }
}
