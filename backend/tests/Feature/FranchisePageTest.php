<?php

namespace Tests\Feature;

use App\Models\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FranchisePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_with_defaults_when_nothing_saved(): void
    {
        $this->get('/franchise')
            ->assertOk()
            ->assertSee('Partner with us')
            ->assertSee('Franchise packages')
            ->assertSee('Kiosk')
            ->assertSee('franchise@bwsuperbakeshop.com');
    }

    public function test_renders_saved_franchise_content(): void
    {
        SiteContent::create(['id' => 1, 'data' => ['franchise' => [
            'hero' => ['eyebrow' => 'Grow with us', 'title' => 'Own a BW branch', 'subtitle' => 'Join us.'],
            'email' => 'invest@bw.test',
            'packages' => [['name' => 'Food Cart', 'price' => '₱500K', 'blurb' => 'Small start', 'features' => ['10 sqm'], 'featured' => true]],
        ]]]);

        $this->get('/franchise')
            ->assertOk()
            ->assertSee('Own a BW branch')
            ->assertSee('Food Cart')
            ->assertSee('invest@bw.test');
    }

    public function test_editor_preview_includes_the_click_to_edit_bridge(): void
    {
        $this->withSession([
            'supabase_user' => ['id' => 'test-id', 'email' => 'editor@bwsuperbakeshop.com', 'name' => 'Test User'],
            'supabase_token_expires_at' => now()->addHour()->timestamp,
        ])->get('/franchise?preview=1')
            ->assertOk()
            ->assertSee('data-editable="franchise.hero.title"', false)
            ->assertSee('bw-editor-bridge');
    }

    public function test_anonymous_preview_request_never_gets_the_edit_bridge(): void
    {
        // ?preview=1 alone isn't enough — Controller::isEditablePreview also
        // requires an editor session, so a leaked/bookmarked preview link
        // can't turn click-to-edit on (which would otherwise intercept and
        // swallow every click on the page) for a real visitor.
        $this->get('/franchise?preview=1')
            ->assertOk()
            ->assertDontSee('bw-editor-bridge');
    }
}
