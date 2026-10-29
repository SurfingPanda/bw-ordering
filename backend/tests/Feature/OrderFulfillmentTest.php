<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\OrderCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_can_be_ordered_with_an_address_and_no_branch(): void
    {
        $product = Product::create(['name' => 'Bread', 'price' => 50]);
        $order = app(OrderCreationService::class)->create([
            'items' => [['product_id' => $product->id, 'qty' => 1]],
            'delivery_type' => 'delivery',
            'payment_method' => 'qrph',
            'address' => '123 Bakery Street, Angeles City',
        ], ['id' => 'customer-1', 'name' => 'Customer', 'email' => 'customer@example.com']);

        $this->assertNull($order->fulfillment_store_id);
        $this->assertNull($order->fulfillment_branch);
        $this->assertSame('123 Bakery Street, Angeles City', $order->address);
        $this->assertSame('grab', $order->delivery_speed);
        $this->assertSame(0.0, (float) $order->delivery);
        $this->assertSame((float) $order->subtotal + (float) $order->vat, (float) $order->total);
    }

    public function test_pickup_still_requires_a_branch(): void
    {
        $this->expectException(ValidationException::class);
        app(OrderCreationService::class)->create([
            'items' => [],
            'delivery_type' => 'pickup',
            'payment_method' => 'cash',
        ], null);
    }
}
