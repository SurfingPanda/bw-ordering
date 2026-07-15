<?php

namespace Tests\Feature;

use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminUsersTest extends TestCase
{
    use RefreshDatabase;

    private function asAdmin(): array
    {
        return [
            'supabase_user' => ['id' => 'admin-id', 'email' => 'bw.redeem@gmail.com', 'name' => 'Admin'],
            'supabase_token_expires_at' => now()->addHour()->timestamp,
        ];
    }

    private function fakeSupabaseUsers(): void
    {
        config(['supabase.url' => 'https://example.supabase.co', 'supabase.service_role_key' => 'svc-key']);
        Http::fake([
            '*/auth/v1/admin/users*' => Http::response(['users' => [
                ['id' => 'u1', 'email' => 'bw.redeem@gmail.com', 'user_metadata' => ['full_name' => 'Crispi'], 'created_at' => '2026-01-01T00:00:00Z', 'last_sign_in_at' => '2026-07-01T00:00:00Z'],
                ['id' => 'u2', 'email' => 'customer@example.com', 'user_metadata' => ['full_name' => 'Cara Customer'], 'created_at' => '2026-02-01T00:00:00Z', 'last_sign_in_at' => null],
            ]], 200),
        ]);
    }

    public function test_non_admin_cannot_open_the_users_page(): void
    {
        $this->withSession([
            'supabase_user' => ['id' => 'x', 'email' => 'customer@example.com', 'contact_number' => '0917'],
            'supabase_token_expires_at' => now()->addHour()->timestamp,
        ])->get('/admin/users')->assertForbidden();
    }

    public function test_admin_sees_accounts_with_roles_and_badges(): void
    {
        $this->fakeSupabaseUsers();

        $this->withSession($this->asAdmin())
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Crispi')
            ->assertSee('Founding admin')
            ->assertSee('Cara Customer')
            ->assertSee('customer@example.com');
    }

    public function test_admin_can_assign_a_role_with_extra_access_in_one_save(): void
    {
        $this->withSession($this->asAdmin())
            ->post('/admin/users/update', [
                'email' => 'staff@example.com',
                'role' => 'editor',
                // 'products' is an editor default — only 'orders' is a true add-on.
                'access' => ['orders', 'products'],
            ])
            ->assertRedirect();

        $this->assertSame('editor', UserRole::roleFor('staff@example.com'));
        $this->assertSame(['orders'], UserRole::grantsFor('staff@example.com'));
    }

    public function test_setting_customer_clears_role_and_grants(): void
    {
        UserRole::create(['email' => 'staff@example.com', 'role' => 'cashier', 'permissions' => ['products']]);

        $this->withSession($this->asAdmin())
            ->post('/admin/users/update', ['email' => 'staff@example.com', 'role' => 'customer'])
            ->assertRedirect();

        $this->assertNull(UserRole::roleFor('staff@example.com'));
        $this->assertSame([], UserRole::grantsFor('staff@example.com'));
    }

    public function test_founding_admin_and_self_demotion_are_blocked(): void
    {
        $this->withSession($this->asAdmin())
            ->post('/admin/users/update', ['email' => 'bw.redeem@gmail.com', 'role' => 'customer'])
            ->assertSessionHasErrors('role');

        $this->assertNull(UserRole::roleFor('bw.redeem@gmail.com')); // untouched
    }
}
