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
     * @return array{ok: bool, message?: string, code?: string, user?: array}
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
                'code' => $response->json('code') ?? $response->json('error_code') ?? $response->json('error'),
                'message' => $response->json('error_description') ?? $response->json('msg') ?? 'Invalid email or password.',
            ];
        }

        return ['ok' => true, 'user' => $response->json('user')];
    }

    /**
     * Public patient registration. Supabase owns the password and sends the
     * verification email; Laravel stores only the local profile shell.
     *
     * @return array{ok: bool, message?: string, code?: string, user?: array}
     */
    public function signUp(string $email, string $password, ?string $redirectTo = null): array
    {
        $payload = [
            'email' => $email,
            'password' => $password,
        ];

        if ($redirectTo) {
            $payload['options'] = ['email_redirect_to' => $redirectTo];
        }

        try {
            $response = $this->client()->post("{$this->url}/auth/v1/signup", $payload);
        } catch (ConnectionException) {
            return ['ok' => false, 'message' => 'Could not connect to the authentication service.'];
        }

        if ($response->failed()) {
            return [
                'ok' => false,
                'code' => $response->json('code') ?? $response->json('error_code') ?? $response->json('error'),
                'message' => $response->json('msg') ?? $response->json('error_description') ?? 'Could not create your account.',
            ];
        }

        return ['ok' => true, 'user' => $response->json('user') ?? $response->json()];
    }

    /**
     * @return array{ok: bool, message?: string, user?: array}
     */
    public function verifyEmailToken(string $tokenHash, string $type = 'email'): array
    {
        try {
            $response = $this->client()->post("{$this->url}/auth/v1/verify", [
                'token_hash' => $tokenHash,
                'type' => $type,
            ]);
        } catch (ConnectionException) {
            return ['ok' => false, 'message' => 'Could not connect to the authentication service.'];
        }

        if ($response->failed()) {
            return [
                'ok' => false,
                'message' => $response->json('msg') ?? $response->json('error_description') ?? 'The verification link is invalid or expired.',
            ];
        }

        return ['ok' => true, 'user' => $response->json('user') ?? data_get($response->json(), 'session.user') ?? []];
    }

    /**
     * Verifies the 6-digit email OTP shown in the Supabase confirmation email.
     *
     * @return array{ok: bool, message?: string, user?: array}
     */
    public function verifyEmailCode(string $email, string $code, string $type = 'email'): array
    {
        try {
            $response = $this->client()->post("{$this->url}/auth/v1/verify", [
                'email' => $email,
                'token' => $code,
                'type' => $type,
            ]);
        } catch (ConnectionException) {
            return ['ok' => false, 'message' => 'Could not connect to the authentication service.'];
        }

        if ($response->failed()) {
            return [
                'ok' => false,
                'message' => $response->json('msg') ?? $response->json('error_description') ?? 'The verification code is invalid or expired.',
            ];
        }

        return ['ok' => true, 'user' => $response->json('user') ?? data_get($response->json(), 'session.user') ?? []];
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    public function resendVerification(string $email, ?string $redirectTo = null): array
    {
        $payload = ['type' => 'signup', 'email' => $email];
        if ($redirectTo) {
            $payload['options'] = ['email_redirect_to' => $redirectTo];
        }

        try {
            $response = $this->client()->post("{$this->url}/auth/v1/resend", $payload);
        } catch (ConnectionException) {
            return ['ok' => false, 'message' => 'Could not connect to the authentication service.'];
        }

        if ($response->failed()) {
            return [
                'ok' => false,
                'message' => $response->json('msg') ?? $response->json('error_description') ?? 'Could not resend the verification email.',
            ];
        }

        return ['ok' => true];
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
