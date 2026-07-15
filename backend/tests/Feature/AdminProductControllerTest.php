<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private function asUser(string $email): array
    {
        return [
            'supabase_user' => ['id' => 'test-id', 'email' => $email, 'name' => 'Test User'],
            'supabase_token_expires_at' => now()->addHour()->timestamp,
        ];
    }

    /** The card fields the editor submits for an existing product row. */
    private function rowFor(Product $product, array $overrides = []): array
    {
        return array_merge([
            'id' => $product->id,
            'name' => $product->name,
            'category' => $product->category,
            'price' => $product->price,
            'original_price' => $product->original_price,
            'description' => $product->description,
            'image_path' => $product->image_path,
            'features' => implode("\n", $product->features ?? []),
            'calories' => $product->calories,
            'status' => $product->status,
        ], $overrides);
    }

    public function test_non_editor_is_forbidden(): void
    {
        $this->withSession($this->asUser('customer@example.com'))
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }

    public function test_admin_sees_the_card_editor_with_products(): void
    {
        Product::factory()->create(['name' => 'Ensaymada']);

        $this->withSession($this->asUser('bw.redeem@gmail.com'))
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Menu Products')
            ->assertSee('Ensaymada')
            ->assertSee('Save changes');
    }

    public function test_editor_can_bulk_save_a_price_change_and_bust_the_cache(): void
    {
        $product = Product::factory()->create(['price' => 100]);
        Cache::put('products.index', 'stale', 600);

        $this->withSession($this->asUser('editor@bwsuperbakeshop.com'))
            ->post(route('admin.products.sync'), [
                'products' => [$this->rowFor($product, ['price' => 150])],
                'originalIds' => [$product->id],
            ])
            ->assertRedirect(route('admin.products.index'));

        $this->assertEquals(150, $product->fresh()->price);
        $this->assertNull(Cache::get('products.index'));
    }

    public function test_a_product_removed_from_the_grid_is_archived_not_deleted(): void
    {
        $kept = Product::factory()->create();
        $removed = Product::factory()->create();

        $this->withSession($this->asUser('bw.redeem@gmail.com'))
            ->post(route('admin.products.sync'), [
                'products' => [$this->rowFor($kept)],
                'originalIds' => [$kept->id, $removed->id],
            ])
            ->assertRedirect(route('admin.products.index'));

        $this->assertNull($kept->fresh()->archived_at);
        $this->assertNotNull($removed->fresh()->archived_at);
        $this->assertDatabaseHas('products', ['id' => $removed->id]);
    }

    public function test_a_new_card_creates_a_product_and_blank_cards_are_skipped(): void
    {
        $this->withSession($this->asUser('editor@bwsuperbakeshop.com'))
            ->post(route('admin.products.sync'), [
                'products' => [
                    ['id' => '', 'name' => 'Ube Pandesal', 'category' => 'Breads', 'price' => 65, 'features' => "Gluten\n\nSoy", 'is_featured' => '1', 'status' => 'bundle'],
                    ['id' => '', 'name' => '   ', 'price' => ''], // untouched blank card
                ],
                'originalIds' => [],
            ])
            ->assertRedirect(route('admin.products.index'));

        $this->assertSame(1, Product::count());
        $created = Product::first();
        $this->assertSame('Ube Pandesal', $created->name);
        $this->assertSame(['Gluten', 'Soy'], $created->features);
        $this->assertTrue($created->is_featured);
        $this->assertSame('bundle', $created->status);
    }
}
