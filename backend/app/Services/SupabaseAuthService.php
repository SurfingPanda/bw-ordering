<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * All server-side traffic to Supabase's GoTrue auth API lives here: verifying
 * a bearer token (shared by the legacy per-request `SupabaseAuth` middleware
 * and the new session bridge), and exchanging credentials for tokens at login
 * time so the browser never has to hold or resend a Supabase JWT itself.
 */
class SupabaseAuthService
{
    /**
     * Verify a Supabase access token and return the normalized user, or null.
     *
     * Two paths, same as before the session bridge existed:
     *   1. Fast, offline HS256 check using SUPABASE_JWT_SECRET (if configured).
     *   2. Fallback: ask Supabase's auth API to validate the token (5 min cache).
     */
    public function verifyAccessToken(string $jwt): ?array
    {
        $secret = config('supabase.jwt_secret');
        if ($secret) {
            $payload = $this->verifyHs256($jwt, $secret);
            if ($payload) {
                return $this->normalize($payload['sub'] ?? null, $payload['email'] ?? null, $payload['user_metadata'] ?? []);
            }
        }

        return $this->verifyViaApi($jwt);
    }

    /**
     * Password-grant login against Supabase's GoTrue API. Returns
     * ['access_token', 'refresh_token', 'expires_in', 'user' => [id,email,name]]
     * on success, or null if the credentials were rejected.
     */
    public function passwordGrant(string $email, string $password): ?array
    {
        $resp = $this->http()->post('/auth/v1/token?grant_type=password', [
            'email' => $email,
            'password' => $password,
        ]);

        if ($resp->failed()) {
            return null;
        }

        return $this->tokenResult($resp->json());
    }

    /** Exchange a refresh token for a new access/refresh token pair. */
    public function refresh(string $refreshToken): ?array
    {
        $resp = $this->http()->post('/auth/v1/token?grant_type=refresh_token', [
            'refresh_token' => $refreshToken,
        ]);

        if ($resp->failed()) {
            return null;
        }

        return $this->tokenResult($resp->json());
    }

    /** Best-effort server-side session revocation; failures are non-fatal. */
    public function logout(string $accessToken): void
    {
        try {
            $this->http()->withToken($accessToken)->post('/auth/v1/logout');
        } catch (\Throwable) {
            // Local session is cleared regardless of whether this succeeds.
        }
    }

    /**
     * GET /auth/v1/user with the signed-in user's token — fresh (uncached)
     * full record for the Profile page, since normalize() keeps only
     * id/email/name and drops metadata like contact_number / avatar_url.
     */
    public function fetchUser(string $accessToken): ?array
    {
        try {
            $resp = $this->http()->withToken($accessToken)->get('/auth/v1/user');
        } catch (\Throwable) {
            return null;
        }

        return $resp->ok() && $resp->json('id') ? $resp->json() : null;
    }

    /**
     * PUT /auth/v1/user as the signed-in user. GoTrue accepts user_metadata
     * under `data` and/or a new `password` — same endpoint the old SPA's
     * supabase.auth.updateUser() used. Returns [updatedRawUser|null, error|null].
     */
    public function updateUser(string $accessToken, array $payload): array
    {
        try {
            $resp = $this->http()->withToken($accessToken)->put('/auth/v1/user', $payload);
        } catch (\Throwable) {
            return [null, 'Could not reach the authentication service. Please try again.'];
        }

        if ($resp->failed()) {
            $error = $resp->json('msg') ?? $resp->json('message') ?? $resp->json('error_description');

            return [null, is_string($error) && $error !== '' ? $error : 'The update was rejected. Please try again.'];
        }

        return [$resp->json(), null];
    }

    /**
     * Email + password sign-up (GoTrue /auth/v1/signup) with user_metadata
     * under `data` — same endpoint the old SPA's supabase.auth.signUp() used.
     * Returns [rawResponseBody|null, errorMessage|null].
     */
    public function signUp(string $email, string $password, array $meta = []): array
    {
        try {
            $resp = $this->http()->post('/auth/v1/signup', [
                'email' => $email,
                'password' => $password,
                'data' => $meta,
            ]);
        } catch (\Throwable) {
            return [null, 'Could not reach the authentication service. Please try again.'];
        }

        if ($resp->failed()) {
            $error = $resp->json('msg') ?? $resp->json('message') ?? $resp->json('error_description');

            return [null, is_string($error) && $error !== '' ? $error : 'Unable to register. Please try again.'];
        }

        return [$resp->json(), null];
    }

    /**
     * POST /auth/v1/recover — emails a password-reset link that lands on
     * `redirectTo` (the URL must be in Supabase's auth Redirect URLs
     * allow-list). GoTrue answers 200 whether or not the address exists, so a
     * null (success) here never leaks which emails are registered — an error
     * message comes back only for transport/rate-limit failures.
     */
    public function sendPasswordReset(string $email, string $redirectTo): ?string
    {
        try {
            $resp = $this->http()->post('/auth/v1/recover?redirect_to='.urlencode($redirectTo), [
                'email' => $email,
            ]);
        } catch (\Throwable) {
            return 'Could not reach the authentication service. Please try again.';
        }

        if ($resp->status() === 429) {
            return 'Too many reset requests. Please wait a moment and try again.';
        }
        if ($resp->failed()) {
            return 'Unable to send the reset link. Please try again.';
        }

        return null;
    }

    /**
     * True when another account already holds this normalized number — the
     * `contact_number_taken` SECURITY DEFINER RPC the SPA's register form
     * called before sign-up. Null when the check itself failed.
     */
    public function contactNumberTaken(string $normalized): ?bool
    {
        try {
            $resp = $this->http()->post('/rest/v1/rpc/contact_number_taken', [
                'p_number' => $normalized,
            ]);
        } catch (\Throwable) {
            return null;
        }

        if ($resp->failed()) {
            return null;
        }

        return (bool) $resp->json();
    }

    /**
     * Upsert the normalized number into Supabase's `profiles` row (PostgREST,
     * service_role key). A 409 means another account's row already holds the
     * number — the UNIQUE constraint is the gatekeeper, exactly like the SPA.
     * Returns an error message, or null on success.
     */
    public function claimContactNumber(string $userId, string $normalized): ?string
    {
        $url = $this->baseUrl();
        $key = (string) config('supabase.service_role_key');
        if (! $url || ! $key || $userId === '') {
            return 'Contact number updates are not available right now.';
        }

        try {
            $resp = Http::withHeaders([
                'apikey' => $key,
                'Authorization' => "Bearer {$key}",
                'Prefer' => 'resolution=merge-duplicates,return=minimal',
            ])->asJson()->timeout(8)->post("{$url}/rest/v1/profiles?on_conflict=id", [[
                'id' => $userId,
                'contact_number' => $normalized,
                'updated_at' => now()->toIso8601String(),
            ]]);
        } catch (\Throwable) {
            return 'Could not reach the profile service. Please try again.';
        }

        if ($resp->status() === 409) {
            return 'This contact number is already in use by another account.';
        }
        if ($resp->failed()) {
            return 'Unable to update your contact number. Please try again.';
        }

        return null;
    }

    private function tokenResult(?array $body): ?array
    {
        if (! $body || empty($body['access_token']) || empty($body['user']['id'])) {
            return null;
        }

        return [
            'access_token' => $body['access_token'],
            'refresh_token' => $body['refresh_token'] ?? null,
            'expires_in' => (int) ($body['expires_in'] ?? 3600),
            'user' => $this->normalize($body['user']['id'], $body['user']['email'] ?? null, $body['user']['user_metadata'] ?? []),
        ];
    }

    private function http()
    {
        return Http::baseUrl($this->baseUrl())
            ->withHeaders(['apikey' => (string) config('supabase.anon_key')])
            ->acceptJson()
            ->asJson()
            ->timeout(8);
    }

    private function verifyViaApi(string $jwt): ?array
    {
        $url = $this->baseUrl();
        $anon = (string) config('supabase.anon_key');
        if (! $url || ! $anon) {
            return null;
        }

        $cacheKey = 'sb_user_'.hash('sha256', $jwt);
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            $resp = Http::withHeaders([
                'apikey' => $anon,
                'Authorization' => "Bearer {$jwt}",
            ])->timeout(8)->get("{$url}/auth/v1/user");
        } catch (\Throwable) {
            return null;
        }

        if ($resp->failed() || empty($resp->json('id'))) {
            return null;
        }

        $user = $this->normalize($resp->json('id'), $resp->json('email'), $resp->json('user_metadata') ?? []);
        Cache::put($cacheKey, $user, 300); // 5 min; access tokens live ~1h

        return $user;
    }

    private function normalize(?string $id, ?string $email, array $meta): array
    {
        return [
            'id' => $id,
            'email' => $email ?? ($meta['email'] ?? null),
            'name' => $meta['full_name'] ?? $meta['name'] ?? null,
            // OAuth sign-ups arrive without one; EnsureSupabaseSession sends
            // them to /complete-profile until it's filled in.
            'contact_number' => $meta['contact_number'] ?? null,
        ];
    }

    /** Verify an HS256 JWT signature + expiry; return the decoded payload or null. */
    private function verifyHs256(string $jwt, string $secret): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }
        [$header, $payload, $signature] = $parts;

        $expected = $this->b64UrlEncode(
            hash_hmac('sha256', $header.'.'.$payload, $secret, true)
        );
        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $decoded = json_decode((string) $this->b64UrlDecode($payload), true);
        if (! is_array($decoded)) {
            return null;
        }
        if (isset($decoded['exp']) && time() >= (int) $decoded['exp']) {
            return null;
        }

        return $decoded;
    }

    private function b64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function b64UrlDecode(string $data): string|false
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('supabase.url'), '/');
    }
}
