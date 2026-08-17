<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuHeaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_back_to_home_not_sign_in(): void
    {
        $response = $this->get(route('menu'));

        $response->assertOk()
            ->assertSee('← Back to home', false)
            ->assertDontSee('Signed in as');
    }

    public function test_signed_in_customer_sees_account_dropdown_without_admin_badge(): void
    {
        $response = $this->withSession([
            'supabase_user' => ['id' => 'x', 'email' => 'customer@example.com', 'name' => 'Jane Doe'],
            'supabase_token_expires_at' => now()->addHour()->timestamp,
        ])->get(route('menu'));

        $response->assertOk()
            ->assertSee('account-menu-btn', false)
            ->assertSee('Signed in as')
            ->assertSee('Jane')
            ->assertDontSee('>Admin<', false)
            ->assertDontSee('>Cashier<', false);
    }

    public function test_admin_sees_admin_badge(): void
    {
        $response = $this->withSession([
            'supabase_user' => ['id' => 'x', 'email' => 'bw.redeem@gmail.com', 'name' => 'Admin'],
            'supabase_token_expires_at' => now()->addHour()->timestamp,
        ])->get(route('menu'));

        $response->assertOk()->assertSee('>Admin<', false);
    }

    public function test_category_bar_is_sticky_on_mobile_and_a_floating_cart_button_exists(): void
    {
        $response = $this->get(route('menu'));

        $response->assertOk()
            // sticky below lg, turned back off (lg:static) so desktop's own
            // height-locked layout (only the main column scrolls) is unchanged.
            ->assertSee('sticky top-0 z-30', false)
            ->assertSee('lg:static', false)
            // reachable from anywhere on the page without scrolling to the header.
            ->assertSee('id="open-cart-fab"', false)
            ->assertSee('cart-count-icon', false);
    }

    public function test_category_selection_keeps_the_url_and_browser_history_in_sync(): void
    {
        $this->get(route('menu'))
            ->assertOk()
            ->assertSee('syncCategoryUrl()', false)
            ->assertSee("history.pushState({ category: active }", false)
            ->assertSee("window.addEventListener('popstate'", false);
    }
}
