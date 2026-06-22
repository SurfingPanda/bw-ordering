<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /** The Supabase user attached by SupabaseAuth middleware (id/email/name). */
    protected function supabaseUser(Request $request): ?array
    {
        return $request->attributes->get('supabase_user');
    }

    protected function isAdmin(?string $email): bool
    {
        if (! $email) {
            return false;
        }
        $list = array_map('strtolower', config('supabase.admin_emails', []));

        return in_array(strtolower($email), $list, true);
    }

    protected function isEditor(?string $email): bool
    {
        if ($this->isAdmin($email)) {
            return true;
        }
        if (! $email) {
            return false;
        }
        $list = array_map('strtolower', config('supabase.editor_emails', []));

        return in_array(strtolower($email), $list, true);
    }

    protected function isHr(?string $email): bool
    {
        if ($this->isAdmin($email)) {
            return true;
        }
        if (! $email) {
            return false;
        }
        $list = array_map('strtolower', config('supabase.hr_emails', []));

        return in_array(strtolower($email), $list, true);
    }
}
