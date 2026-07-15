<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Signing you in…</title>
    <meta name="robots" content="noindex">
</head>
<body style="background:#0b1f3d;color:#fff;font-family:sans-serif;display:flex;min-height:100vh;align-items:center;justify-content:center;">
    <p>{{ $message ?? 'Signing you in…' }}</p>

    {{--
        Temporary bridge for the Vite/React SPA's admin pages, which are not
        yet ported to Blade (see Phase 4 of the migration) and read their auth
        state from a browser-side Supabase JS session in localStorage. Our
        server-side login/logout already establishes/clears the Laravel
        session; this script mirrors that into the Supabase JS SDK's storage
        so the SPA's ProtectedRoute/AdminRoute guards keep working unchanged.
        Delete this view (and its two call sites in SessionController) once
        the admin dashboards are ported and nothing reads a browser session.
    --}}
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2/dist/umd/supabase.js"></script>
    <script>
        (async () => {
            try {
                const client = window.supabase.createClient(
                    @json(config('supabase.url')),
                    @json(config('supabase.anon_key')),
                );

                @if($action === 'login')
                    await client.auth.setSession({
                        access_token: @json($accessToken),
                        refresh_token: @json($refreshToken),
                    });
                @else
                    await client.auth.signOut();
                @endif
            } catch (e) {
                // Non-fatal: worst case the SPA's admin pages ask this user
                // to sign in again next time they visit one directly.
            } finally {
                window.location.replace(@json($redirectTo));
            }
        })();
    </script>
</body>
</html>
