<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                // Pass plaintext; the User model's `hashed` cast hashes it with the
                // configured driver (argon2id). Pre-hashing with bcrypt() makes the
                // cast's Hash::verifyConfiguration() reject the non-argon2id hash.
                'password' => 'password',
            ]
        );

        $this->call(StoreSeeder::class);
        $this->call(VoucherSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(UserRoleSeeder::class);
    }
}
