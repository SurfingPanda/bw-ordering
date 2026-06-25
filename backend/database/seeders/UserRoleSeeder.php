<?php

namespace Database\Seeders;

use App\Models\UserRole;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    /**
     * Seed the default cashier. The matching Supabase login (originally
     * admin@bwsuperbakeshop.com) is renamed to cashier@bwsuperbakeshop.com from
     * the admin Users page; this row gives that email the cashier role.
     */
    public function run(): void
    {
        UserRole::updateOrCreate(
            ['email' => 'cashier@bwsuperbakeshop.com'],
            ['role' => 'cashier'],
        );
    }
}
