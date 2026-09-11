<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A single role assignment keyed by (lowercased) email. See the
// create_user_roles_table migration for how this layers over the env
// allowlists.
class UserRole extends Model
{
    protected $fillable = ['email', 'role', 'permissions'];

    /** The roles that can be stored. 'customer' is represented by no row. */
    public const ROLES = ['admin', 'editor', 'cashier'];

    /** Admin sections that can be granted per user beyond the role defaults
     *  (see Controller::ROLE_SECTIONS for what each role gets by default). */
    public const SECTIONS = ['orders', 'custom-cakes', 'contact-messages', 'assistant-chats', 'products', 'content', 'stores', 'vouchers'];

    protected function casts(): array
    {
        return ['permissions' => 'array'];
    }

    /** Look up the stored role for an email, or null if none. */
    public static function roleFor(?string $email): ?string
    {
        if (! $email) {
            return null;
        }

        return static::where('email', strtolower($email))->value('role');
    }

    /** Extra section grants stored for an email (beyond its role defaults). */
    public static function grantsFor(?string $email): array
    {
        if (! $email) {
            return [];
        }

        return (array) (static::where('email', strtolower($email))->first()?->permissions ?? []);
    }
}
