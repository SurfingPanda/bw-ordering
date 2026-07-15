<?php

namespace Tests\Feature;

use App\Models\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function asEditor(): array
    {
        return [
            'supabase_user' => ['id' => 'test-id', 'email' => 'editor@bwsuperbakeshop.com', 'name' => 'Ed'],
            'supabase_token_expires_at' => now()->addHour()->timestamp,
        ];
    }

    public function test_non_editor_cannot_stage_a_preview_draft(): void
    {
        $this->withSession([
            'supabase_user' => ['id' => 'x', 'email' => 'customer@example.com'],
            'supabase_token_expires_at' => now()->addHour()->timestamp,
        ])->post(route('admin.content.preview'), ['announcement' => 'hacked'])
            ->assertForbidden();
    }

    public function test_editor_draft_shows_on_landing_only_with_preview_flag(): void
    {
        SiteContent::create(['id' => 1, 'data' => ['announcement' => 'Saved announcement']]);

        // Stage an unsaved draft (does not touch the DB).
        $this->withSession($this->asEditor())
            ->post(route('admin.content.preview'), ['announcement' => 'DRAFT banner text'])
            ->assertNoContent();

        // The DB row is untouched — a normal visitor still sees the saved copy.
        $this->assertSame('Saved announcement', SiteContent::find(1)->data['announcement']);
        $this->get('/')->assertOk()->assertSee('Saved announcement')->assertDontSee('DRAFT banner text');

        // The editor, with ?preview=1, sees the draft.
        $this->withSession($this->asEditor())
            ->get('/?preview=1')
            ->assertOk()
            ->assertSee('DRAFT banner text');
    }

    public function test_a_guest_with_preview_flag_never_sees_a_draft(): void
    {
        SiteContent::create(['id' => 1, 'data' => ['announcement' => 'Saved announcement']]);

        // Draft staged in an editor session…
        $this->withSession($this->asEditor())
            ->post(route('admin.content.preview'), ['announcement' => 'secret draft']);

        // …but a guest hitting ?preview=1 (no editor session) sees only saved content.
        $this->get('/?preview=1')->assertOk()->assertDontSee('secret draft')->assertSee('Saved announcement');
    }
}
