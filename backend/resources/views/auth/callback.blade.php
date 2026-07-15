<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Signing you in…</title>
    <meta name="robots" content="noindex">
</head>
<body style="background:#0b1f3d;color:#fff;font-family:sans-serif;display:flex;min-height:100vh;align-items:center;justify-content:center;">
    <p>Signing you in…</p>

    {{--
        OAuth landing spot (see SessionController::oauthCallback). Supabase
        redirects here with the session in the URL #fragment
        (#access_token=…&refresh_token=…), which never reaches the server —
        so forward it with a same-origin POST and let the server verify the
        token and put it in the Laravel session. The fragment is replaced by
        the POST navigation, so the tokens don't linger in the address bar
        or history.
    --}}
    <script>
        (function () {
            var params = new URLSearchParams(window.location.hash.replace(/^#/, ''));
            var access = params.get('access_token');
            var refresh = params.get('refresh_token');

            // Provider denied / cancelled / misconfigured — back to the form.
            if (!access || !refresh) {
                window.location.replace(@json(route('login', ['oauth' => 'failed'])));
                return;
            }

            var form = document.createElement('form');
            form.method = 'POST';
            form.action = @json(route('oauth.store'));
            var fields = {
                _token: @json(csrf_token()),
                access_token: access,
                refresh_token: refresh,
                expires_in: params.get('expires_in') || ''
            };
            Object.keys(fields).forEach(function (name) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = fields[name];
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
        })();
    </script>
</body>
</html>
