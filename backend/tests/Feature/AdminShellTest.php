<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminShellTest extends TestCase
{
    use RefreshDatabase;

    private function asUser(string $email): array
    {
        return [
            'supabase_user' => ['id' => 'test-id', 'email' => $email, 'name' => 'Test User'],
            'supabase_token_expires_at' => now()->addHour()->timestamp,
        ];
    }

    public function test_admin_route_redirects_into_the_site_editor(): void
    {
        $this->withSession($this->asUser('bw.redeem@gmail.com'))
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.content'));
    }

    public function test_admin_sees_the_admin_group_in_the_editor_sidebar(): void
    {
        $this->withSession($this->asUser('bw.redeem@gmail.com'))
            ->get(route('admin.content'))
            ->assertOk()
            ->assertSee('Users &amp; Roles', false)
            ->assertSee('Orders')
            ->assertSee('Custom Cakes');
    }

    public function test_plain_editor_does_not_see_the_admin_group(): void
    {
        $this->withSession($this->asUser('editor@bwsuperbakeshop.com'))
            ->get(route('admin.content'))
            ->assertOk()
            ->assertDontSee('Users &amp; Roles', false);
    }

    public function test_products_page_uses_the_site_editor_shell(): void
    {
        $this->withSession($this->asUser('bw.redeem@gmail.com'))
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Site Editor')
            ->assertSee('Menu Products');
    }

    public function test_non_admin_cannot_reach_the_users_placeholder(): void
    {
        $this->withSession($this->asUser('editor@bwsuperbakeshop.com'))
            ->get(route('admin.users'))
            ->assertForbidden();
    }

    public function test_editor_can_reach_the_content_placeholder(): void
    {
        $this->withSession($this->asUser('editor@bwsuperbakeshop.com'))
            ->get(route('admin.content'))
            ->assertOk();
    }
}
