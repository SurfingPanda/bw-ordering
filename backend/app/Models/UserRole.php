<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A single role assignment keyed by (lowercased) email. See the
// create_user_roles_table migration for how this layers over the env
// allowlists.
class UserRole extends Model
{
    protected $fillable = ['email', 'role'];

    /** The roles that can be stored. 'customer' is represented by no row. */
    public const ROLES = ['admin', 'editor', 'cashier', 'hr'];

    /** Look up the stored role for an email, or null if none. */
    public static function roleFor(?string $email): ?string
    {
        if (! $email) {
            return null;
        }

        return static::where('email', strtolower($email))->value('role');
    }
}
