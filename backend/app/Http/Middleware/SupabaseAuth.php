<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

// Verifies a Supabase access token and attaches a normalized user to the
// request as `supabase_user`.
//
// Two verification paths:
//   1. Fast, offline HS256 check using SUPABASE_JWT_SECRET (if configured).
//   2. Fallback: ask Supabase's auth API to validate the token (needs only the
//      anon key, works for asymmetric signing keys, and catches revoked tokens).
// Path 2 means the app works even when the JWT secret isn't set.
class SupabaseAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = $this->resolveUser($token);
        if (! $user) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        $request->attributes->set('supabase_user', $user);

        return $next($request);
    }

    private function resolveUser(string $jwt): ?array
    {
        // 1. Offline HS256 verification when the shared JWT secret is set.
        $secret = config('supabase.jwt_secret');
        if ($secret) {
            $payload = $this->verifyHs256($jwt, $secret);
            if ($payload) {
                return $this->normalize($payload['sub'] ?? null, $payload['email'] ?? null, $payload['user_metadata'] ?? []);
            }
        }

        // 2. Fallback: validate against Supabase's auth API (cached briefly).
        return $this->verifyViaApi($jwt);
    }

    private function verifyViaApi(string $jwt): ?array
    {
        $url = rtrim((string) config('supabase.url'), '/');
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
}
