<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminSiteContentTest extends TestCase
{
    use RefreshDatabase;

    private function asUser(string $email): array
    {
        return [
            'supabase_user' => ['id' => 'test-id', 'email' => $email, 'name' => 'Test User'],
            'supabase_token_expires_at' => now()->addHour()->timestamp,
        ];
    }

    public function test_non_editor_is_forbidden(): void
    {
        $this->withSession($this->asUser('customer@example.com'))
            ->get(route('admin.content'))
            ->assertForbidden();
    }

    public function test_editor_sees_the_form_prefilled_with_saved_content(): void
    {
        SiteContent::create(['id' => 1, 'data' => ['announcement' => 'Fresh pandesal at 6am!']]);

        $this->withSession($this->asUser('editor@bwsuperbakeshop.com'))
            ->get(route('admin.content'))
            ->assertOk()
            ->assertSee('Fresh pandesal at 6am!')
            ->assertSee('Save changes');
    }

    public function test_a_fresh_site_prefills_the_form_with_the_public_page_defaults(): void
    {
        $this->withSession($this->asUser('editor@bwsuperbakeshop.com'))
            ->get(route('admin.content'))
            ->assertOk()
            // From LandingController::DEFAULT_CONTENT — the same copy the
            // public landing renders before anything is saved.
            ->assertSee('Free delivery on orders over');
    }

    public function test_saving_merges_managed_keys_and_preserves_unmanaged_ones(): void
    {
        SiteContent::create(['id' => 1, 'data' => [
            'announcement' => 'old',
            'someFutureSection' => ['hero' => ['title' => 'Bake your career with us']],
            'bestSellers' => [['name' => 'SPA-era card']],
        ]]);
        Cache::put('site-content', 'stale', 600);

        $this->withSession($this->asUser('editor@bwsuperbakeshop.com'))
            ->put(route('admin.content.update'), [
                'section' => 'announcement',
                'announcement' => 'New announcement',
                'banners' => [3 => ['img' => '/images/a.png', 'alt' => 'A']],
                'maintenance' => ['enabled' => '1', 'title' => 'BRB', 'message' => 'Down for a bit'],
            ])
            ->assertRedirect(route('admin.content', ['section' => 'announcement']))
            ->assertSessionHas('status', 'Content saved.');

        $data = SiteContent::find(1)->data;
        $this->assertSame('New announcement', $data['announcement']);
        // Repeater rows are reindexed sequentially.
        $this->assertSame([['img' => '/images/a.png', 'alt' => 'A']], $data['banners']);
        $this->assertTrue($data['maintenance']['enabled']);
        // Keys this form doesn't manage survive a save untouched.
        $this->assertSame('Bake your career with us', $data['someFutureSection']['hero']['title']);
        $this->assertSame('SPA-era card', $data['bestSellers'][0]['name']);
        $this->assertNull(Cache::get('site-content'));
    }

    public function test_package_features_textarea_becomes_an_array(): void
    {
        $this->withSession($this->asUser('editor@bwsuperbakeshop.com'))
            ->put(route('admin.content.update'), [
                'franchise' => [
                    'hero' => ['title' => 'Partner with us'],
                    'packages' => [
                        ['name' => 'Kiosk', 'features' => "25 sqm\n\nCore menu\n", 'featured' => '1'],
                    ],
                ],
            ])
            ->assertRedirect();

        $pkg = SiteContent::find(1)->data['franchise']['packages'][0];
        $this->assertSame(['25 sqm', 'Core menu'], $pkg['features']);
        $this->assertTrue($pkg['featured']);
    }

    public function test_franchise_section_toggles_persist_and_hide_the_section(): void
    {
        $this->withSession($this->asUser('editor@bwsuperbakeshop.com'))
            ->put(route('admin.content.update'), [
                'franchise' => [
                    'hero' => ['title' => 'Partner with us'],
                    // hidden+checkbox pair: only the hidden "0" arrives when
                    // the toggle is off; "1" when it's on.
                    'visible' => ['hero' => '0', 'perks' => '1'],
                ],
            ])
            ->assertRedirect();

        $visible = SiteContent::find(1)->data['franchise']['visible'];
        $this->assertFalse($visible['hero']);
        $this->assertTrue($visible['perks']);

        $page = $this->get('/franchise');
        $page->assertOk()
            // Hero is hidden; perks (default content) still render.
            ->assertDontSee('Partner with a trusted, decades-old brand')
            ->assertSee('Why franchise with us');
    }

    public function test_invalid_franchise_email_is_rejected_and_the_edit_is_preserved(): void
    {
        $this->withSession($this->asUser('editor@bwsuperbakeshop.com'))
            ->from(route('admin.content'))
            ->put(route('admin.content.update'), [
                'franchise' => [
                    'hero' => ['title' => 'Partner with us'],
                    'email' => 'not-an-email',
                ],
            ])
            ->assertRedirect(route('admin.content'))
            ->assertSessionHasErrors('franchise.email');

        // The bad value never reached the DB...
        $this->assertNull(SiteContent::find(1));
        // ...but is flashed back so the editor doesn't have to retype the
        // whole section (previously the whole form re-rendered from the DB
        // on any failure, silently discarding every unsaved edit).
        $this->assertSame('not-an-email', session()->getOldInput('franchise.email'));
        $this->assertSame('Partner with us', session()->getOldInput('franchise.hero.title'));
    }

    public function test_failed_save_redisplays_the_submitted_value_instead_of_the_saved_one(): void
    {
        SiteContent::create(['id' => 1, 'data' => ['franchise' => ['email' => 'old@bwsuperbakeshop.com']]]);

        $this->withSession($this->asUser('editor@bwsuperbakeshop.com'))
            ->from(route('admin.content'))
            ->put(route('admin.content.update'), [
                'franchise' => [
                    'hero' => ['title' => 'Partner with us'],
                    'email' => 'not-an-email',
                ],
            ])
            ->assertSessionHasErrors('franchise.email');

        // Reloading the page shows what was just typed, not what's still
        // saved in the DB — the view now honors old() over $content.
        $this->get(route('admin.content'))
            ->assertOk()
            ->assertSee('not-an-email')
            ->assertDontSee('old@bwsuperbakeshop.com');
    }

    public function test_non_numeric_bundle_price_is_rejected(): void
    {
        $this->withSession($this->asUser('editor@bwsuperbakeshop.com'))
            ->put(route('admin.content.update'), [
                'menuPromo' => [
                    'slides' => [
                        ['title' => 'Ube Bundle', 'bundlePrice' => '1o0'],
                    ],
                ],
            ])
            ->assertSessionHasErrors('menuPromo.slides.0.bundlePrice');

        $this->assertNull(SiteContent::find(1));
    }

    public function test_save_categories_persists_declared_list_and_images(): void
    {
        $this->withSession($this->asUser('editor@bwsuperbakeshop.com'))
            ->post(route('admin.content.categories'), [
                'menuCategories' => ['Cakes', ' Seasonal ', ''],
                'menuCategoryImages' => ['Cakes' => '/images/cakes.png', 'Seasonal' => '  '],
            ])
            ->assertRedirect(route('admin.content', ['section' => 'menuCategories']));

        $data = SiteContent::find(1)->data;
        $this->assertSame(['Cakes', 'Seasonal'], $data['menuCategories']);
        $this->assertSame(['Cakes' => '/images/cakes.png'], $data['menuCategoryImages']);
    }

    public function test_renaming_a_category_moves_its_products(): void
    {
        Product::factory()->create(['category' => 'Bread']);
        SiteContent::create(['id' => 1, 'data' => [
            'menuCategories' => ['Bread'],
            'menuCategoryImages' => ['Bread' => '/images/bread.png'],
        ]]);

        $this->withSession($this->asUser('editor@bwsuperbakeshop.com'))
            ->post(route('admin.content.categories.rename', 'Bread'), [
                'rename_to' => ['Bread' => 'Breads'],
            ])
            ->assertRedirect(route('admin.content', ['section' => 'menuCategories']));

        $this->assertSame(1, Product::where('category', 'Breads')->count());
        $data = SiteContent::find(1)->data;
        $this->assertSame(['Breads'], $data['menuCategories']);
        $this->assertSame(['Breads' => '/images/bread.png'], $data['menuCategoryImages']);
    }

    public function test_deleting_a_category_reassigns_its_products(): void
    {
        Product::factory()->create(['category' => 'Bread']);

        $this->withSession($this->asUser('editor@bwsuperbakeshop.com'))
            ->post(route('admin.content.categories.delete', 'Bread'), [
                'delete_to' => ['Bread' => 'Pastries'],
            ])
            ->assertRedirect(route('admin.content', ['section' => 'menuCategories']));

        $this->assertSame(1, Product::where('category', 'Pastries')->count());
    }
}
