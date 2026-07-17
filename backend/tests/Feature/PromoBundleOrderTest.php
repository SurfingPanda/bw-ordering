<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SiteContent;
use App\Models\Store;
use App\Services\OrderCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Promo-bundle order lines: OrderCreationService::resolveBundle() must price a
 * bundle from the *saved* Menu Promo slide (never from the client) and reject
 * id sets that don't exactly match a saved slide.
 */
class PromoBundleOrderTest extends TestCase
{
    use RefreshDatabase;

    private Product $roll;

    private Product $hopia;

    private Store $store;

    private const USER = ['id' => 'user-1', 'email' => 'customer@example.com', 'name' => 'Cara'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->roll = Product::create(['name' => 'Ube Roll', 'price' => 379]);
        $this->hopia = Product::create(['name' => 'Hopia Ube', 'price' => 60]);
        $this->store = Store::create(['name' => 'Main Branch', 'address' => '123 Bakery St', 'fulfillment' => 'both']);
    }

    private function savePromo(?float $bundlePrice): void
    {
        SiteContent::updateOrCreate(['id' => 1], ['data' => ['menuPromo' => [
            'enabled' => true,
            'slides' => [[
                'title' => 'Ube Lovers Bundle',
                'products' => [$this->roll->id, $this->hopia->id],
                'bundlePrice' => $bundlePrice,
            ]],
        ]]]);
    }

    private function placeBundleOrder(array $productIds, int $qty = 1)
    {
        return app(OrderCreationService::class)->create([
            'items' => [['bundle_products' => $productIds, 'qty' => $qty]],
            'payment_method' => 'cash',
            'delivery_type' => 'pickup',
            'fulfillment_store_id' => $this->store->id,
        ], self::USER);
    }

    public function test_bundle_is_charged_the_saved_bundle_price(): void
    {
        $this->savePromo(399.0);

        $order = $this->placeBundleOrder([$this->roll->id, $this->hopia->id], 2);

        $this->assertSame(798.0, (float) $order->subtotal); // 2 × ₱399, not 2 × ₱439
        $item = $order->items[0];
        $this->assertTrue($item['bundle']);
        $this->assertSame('Ube Lovers Bundle', $item['name']);
        $this->assertSame(399.0, (float) $item['price']);
        $this->assertSame(
            [$this->roll->id, $this->hopia->id],
            array_column($item['products'], 'product_id')
        );
    }

    public function test_blank_bundle_price_charges_the_regular_total(): void
    {
        $this->savePromo(null);

        $order = $this->placeBundleOrder([$this->roll->id, $this->hopia->id]);

        $this->assertSame(439.0, (float) $order->subtotal);
    }

    public function test_an_id_set_matching_no_saved_slide_is_rejected(): void
    {
        $this->savePromo(399.0);

        // A subset of the promo's products must not get the promo price.
        $this->expectException(ValidationException::class);
        $this->placeBundleOrder([$this->roll->id]);
    }

    public function test_a_sold_out_component_blocks_the_bundle(): void
    {
        $this->savePromo(399.0);
        $this->hopia->update(['status' => 'sold_out']);

        $this->expectException(ValidationException::class);
        $this->placeBundleOrder([$this->roll->id, $this->hopia->id]);
    }
}
