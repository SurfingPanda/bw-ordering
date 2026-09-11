<?php

namespace Tests\Feature;

use App\Models\SiteContent;
use App\Models\SiteRating;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSiteRatingTest extends TestCase
{
    use RefreshDatabase;

    private function editorSession(): array
    {
        return [
            'supabase_user' => ['id' => 'editor-id', 'email' => 'editor@bwsuperbakeshop.com', 'name' => 'Test Editor'],
            'supabase_token_expires_at' => now()->addHour()->timestamp,
        ];
    }

    public function test_editor_can_view_prompt_settings_and_rating_report(): void
    {
        SiteRating::create(['rating' => 5, 'page' => '/']);
        SiteRating::create(['rating' => 3, 'page' => '/']);

        $this->withSession($this->editorSession())
            ->get(route('admin.ratings'))
            ->assertOk()
            ->assertSee('Prompt settings')
            ->assertSee('Recent ratings')
            ->assertSee('4')
            ->assertSee('ratings received');
    }

    public function test_editor_can_update_rating_prompt_settings(): void
    {
        $this->withSession($this->editorSession())
            ->put(route('admin.ratings.settings'), ['enabled' => '0', 'delaySeconds' => 12])
            ->assertRedirect(route('admin.ratings'));

        $this->assertSame(['enabled' => false, 'delaySeconds' => 12], SiteContent::find(1)->data['siteRating']);
        $this->get('/')->assertOk()->assertDontSee('id="site-rating-modal"', false);
    }
}
