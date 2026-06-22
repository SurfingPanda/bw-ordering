<?php

// Supabase is the identity provider. The frontend authenticates against
// Supabase and sends the resulting access token (a JWT) as a Bearer token to
// this API; SupabaseAuth middleware verifies it with the shared JWT secret.
// Roles are email allowlists, mirrored from the frontend's VITE_*_EMAILS.

return [
    'jwt_secret' => env('SUPABASE_JWT_SECRET'),
    'url' => env('SUPABASE_URL'),
    'anon_key' => env('SUPABASE_ANON_KEY'),

    'admin_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ADMIN_EMAILS', ''))
    ))),
    'editor_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('EDITOR_EMAILS', ''))
    ))),
    'hr_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('HR_EMAILS', ''))
    ))),
];
