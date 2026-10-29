<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Services\PayMongoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class NativePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.paymongo.secret' => 'sk_test_test', 'services.paymongo.public' => 'pk_test_test', 'services.paymongo.webhook_secret' => 'whsec-test']);
        Cache::flush();
        Http::preventStrayRequests();
    }

    private function sessionFor(string $id = 'customer-1'): array
    {
        return ['supabase_user' => ['id' => $id, 'email' => 'customer@example.com', 'name' => 'Customer']];
    }

    private function order(): Order
    {
        return Order::create([
            'id' => (string) Str::uuid(), 'user_id' => 'customer-1', 'customer_name' => 'Customer', 'customer_email' => 'customer@example.com',
            'customer_phone' => '09171234567', 'items' => [], 'subtotal' => 100, 'total' => 112, 'vat' => 12,
            'status' => 'pending', 'payment_status' => 'pending', 'payment_method' => 'paymongo', 'payment_channel' => 'gcash',
            'payment_ref' => 'pi_test', 'checkout_token' => (string) Str::uuid(), 'delivery_type' => 'delivery', 'delivery_speed' => 'grab', 'address' => 'Home',
        ]);
    }

    private function intent(Order $order, string $status = 'awaiting_payment_method', array $extra = []): array
    {
        return ['data' => ['id' => 'pi_test', 'attributes' => array_merge([
            'amount' => 11200, 'currency' => 'PHP', 'status' => $status, 'metadata' => ['order_id' => $order->id],
        ], $extra)]];
    }

    public function test_decline_reason_is_returned_without_provider_details(): void
    {
        $order = $this->order();
        Http::fake([
            '*/payment_intents/pi_test' => Http::response($this->intent($order)),
            '*/payment_methods' => Http::response(['data' => ['id' => 'pm_wallet']]),
            '*/payment_intents/pi_test/attach' => Http::response(['errors' => [['code' => 'card_declined', 'sub_code' => 'insufficient_funds', 'detail' => 'private-provider-detail']]], 402),
        ]);
        $this->withSession($this->sessionFor())->postJson('/checkout/payment/'.$order->id.'/pay', ['channel' => 'gcash'])
            ->assertStatus(422)->assertJsonPath('payment_failed', true)
            ->assertJsonPath('message', 'Insufficient funds. Use another card or payment method.')
            ->assertDontSee('private-provider-detail');
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_status_includes_safe_last_payment_error(): void
    {
        $order = $this->order();
        Http::fake(['*/payment_intents/pi_test' => Http::response($this->intent($order, 'awaiting_payment_method', [
            'last_payment_error' => ['code' => 'card_declined', 'sub_code' => 'cvc_incorrect'],
        ]))]);
        $this->withSession($this->sessionFor())->getJson('/checkout/payment/'.$order->id.'/status')
            ->assertOk()->assertJsonPath('failed', true)
            ->assertJsonPath('error_message', 'Check your card security code (CVC) and try again.');
    }

    public function test_checkout_creates_one_server_priced_order_for_repeated_submissions(): void
    {
        $product = Product::create(['name' => 'Bread', 'price' => 100]);
        $data = [
            'items' => [['product_id' => $product->id, 'qty' => 1]], 'payment_method' => 'paymongo', 'payment_channel' => 'gcash',
            'checkout_token' => (string) Str::uuid(), 'customer_name' => 'Customer', 'customer_email' => 'customer@example.com',
            'delivery_type' => 'delivery', 'address' => 'Home', 'total' => 1,
        ];
        $first = $this->withSession($this->sessionFor())->postJson('/checkout/payment', $data)->assertOk();
        $second = $this->postJson('/checkout/payment', $data)->assertOk();
        $this->assertSame($first->json('order_id'), $second->json('order_id'));
        $this->assertDatabaseCount('orders', 1);
        $order = Order::firstOrFail();
        $this->assertSame(112.0, (float) $order->total);
        $this->assertSame('pending', $order->payment_status);
        Http::assertNothingSent();
    }

    public function test_payment_page_is_on_our_site_has_card_fields_and_has_no_maya(): void
    {
        $order = $this->order();
        $this->withSession($this->sessionFor())->get('/checkout/payment/'.$order->id)->assertOk()
            ->assertSee('Complete your payment')->assertSee('native-card-number')->assertDontSee('Maya')->assertDontSee('sk_test_test');
        Http::assertNothingSent();
    }

    public function test_another_customer_cannot_view_or_pay_the_order(): void
    {
        $order = $this->order();
        $this->withSession($this->sessionFor('customer-2'))->get('/checkout/payment/'.$order->id)->assertNotFound();
        $this->postJson('/checkout/payment/'.$order->id.'/pay', ['channel' => 'gcash'])->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_qrph_is_generated_on_our_page_and_stays_unpaid_until_verified(): void
    {
        $order = $this->order();
        Http::fake([
            '*/payment_intents/pi_test' => Http::response($this->intent($order)),
            '*/payment_methods' => Http::response(['data' => ['id' => 'pm_qr']]),
            '*/payment_intents/pi_test/attach' => Http::response($this->intent($order, 'awaiting_next_action', ['next_action' => ['code' => ['image_url' => 'data:image/png;base64,AAAA']]])),
        ]);
        $this->withSession($this->sessionFor())->postJson('/checkout/payment/'.$order->id.'/pay', ['channel' => 'qrph'])
            ->assertOk()->assertJsonPath('qr_url', 'data:image/png;base64,AAAA')->assertJsonPath('paid', false);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_gcash_goes_to_wallet_authorization_without_hosted_checkout(): void
    {
        $order = $this->order();
        Http::fake([
            '*/payment_intents/pi_test' => Http::response($this->intent($order)),
            '*/payment_methods' => Http::response(['data' => ['id' => 'pm_wallet']]),
            '*/payment_intents/pi_test/attach' => Http::response($this->intent($order, 'awaiting_next_action', ['next_action' => ['redirect' => ['url' => 'https://wallet.example/authorize']]])),
        ]);
        $this->withSession($this->sessionFor())->postJson('/checkout/payment/'.$order->id.'/pay', ['channel' => 'gcash'])
            ->assertOk()->assertJsonPath('authorization_url', 'https://wallet.example/authorize');
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'checkout_sessions'));
    }

    public function test_card_token_is_attached_and_bank_authentication_is_returned(): void
    {
        $order = $this->order();
        Http::fake([
            '*/payment_intents/pi_test' => Http::response($this->intent($order)),
            '*/payment_methods/pm_card' => Http::response(['data' => ['id' => 'pm_card', 'attributes' => ['type' => 'card']]]),
            '*/payment_intents/pi_test/attach' => Http::response($this->intent($order, 'awaiting_next_action', ['next_action' => ['redirect' => ['url' => 'https://bank.example/3ds']]])),
        ]);
        $this->withSession($this->sessionFor())->postJson('/checkout/payment/'.$order->id.'/pay', ['channel' => 'card', 'payment_method_id' => 'pm_card'])
            ->assertOk()->assertJsonPath('authorization_url', 'https://bank.example/3ds');
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/attach') && $request['data']['attributes']['payment_method'] === 'pm_card');
    }

    public function test_raw_card_numbers_are_rejected_by_our_server(): void
    {
        $order = $this->order();
        $this->withSession($this->sessionFor())->postJson('/checkout/payment/'.$order->id.'/pay', ['channel' => 'card', 'card_number' => '4343434343434345'])
            ->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_an_active_payment_is_reused_instead_of_charged_again(): void
    {
        $order = $this->order();
        Http::fake(['*/payment_intents/pi_test' => Http::response($this->intent($order, 'processing'))]);
        $this->withSession($this->sessionFor())->postJson('/checkout/payment/'.$order->id.'/pay', ['channel' => 'gcash'])->assertOk()->assertJsonPath('status', 'processing');
        Http::assertSentCount(1);
    }

    public function test_polling_marks_paid_only_for_a_matching_succeeded_intent(): void
    {
        $order = $this->order();
        Http::fake(['*/payment_intents/pi_test' => Http::response($this->intent($order, 'succeeded'))]);
        $this->withSession($this->sessionFor())->getJson('/checkout/payment/'.$order->id.'/status')->assertOk()->assertJsonPath('paid', true);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_a_wrong_amount_cannot_mark_the_order_paid(): void
    {
        $order = $this->order();
        Http::fake(['*/payment_intents/pi_test' => Http::response($this->intent($order, 'succeeded', ['amount' => 100]))]);
        $this->withSession($this->sessionFor())->getJson('/checkout/payment/'.$order->id.'/status')->assertStatus(502);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_maya_is_rejected_and_never_sent_to_paymongo(): void
    {
        $order = $this->order();
        $this->withSession($this->sessionFor())->postJson('/checkout/payment/'.$order->id.'/pay', ['channel' => 'paymaya'])->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_a_signed_webhook_is_reverified_with_paymongo_before_marking_paid(): void
    {
        $order = $this->order();
        Http::fake(['*/payment_intents/pi_test' => Http::response($this->intent($order, 'succeeded'))]);
        $body = json_encode(['data' => ['attributes' => ['type' => 'payment.paid', 'data' => ['id' => 'pay_test', 'attributes' => ['payment_intent_id' => 'pi_test']]]]]);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'whsec-test');
        $this->call('POST', '/api/webhooks/paymongo', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_PAYMONGO_SIGNATURE' => "t={$timestamp},te={$signature},li="], $body)->assertOk();
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_unsigned_webhooks_are_rejected_even_without_configuration(): void
    {
        config(['services.paymongo.webhook_secret' => null]);
        $this->assertFalse(app(PayMongoService::class)->verifyWebhook(null, '{}'));
        $this->postJson('/api/webhooks/paymongo', [])->assertStatus(400);
    }

    public function test_the_intent_is_created_once_with_the_trusted_order_total_and_allowed_methods(): void
    {
        $order = $this->order();
        $order->update(['payment_ref' => null]);
        Http::fake([
            '*/payment_intents' => Http::response($this->intent($order)),
            '*/payment_intents/pi_test' => Http::response($this->intent($order)),
        ]);
        $this->withSession($this->sessionFor())->getJson('/checkout/payment/'.$order->id.'/status')->assertOk();
        $this->getJson('/checkout/payment/'.$order->id.'/status')->assertOk();
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request['data']['attributes']['amount'] === 11200
            && $request['data']['attributes']['payment_method_allowed'] === ['gcash', 'card', 'qrph']
            && $request['data']['attributes']['metadata']['order_id'] === $order->id);
        Http::assertSentCount(2);
    }

    public function test_an_intent_bound_to_another_order_cannot_mark_this_order_paid(): void
    {
        $order = $this->order();
        Http::fake(['*/payment_intents/pi_test' => Http::response($this->intent($order, 'succeeded', ['metadata' => ['order_id' => (string) Str::uuid()]]))]);
        $this->withSession($this->sessionFor())->getJson('/checkout/payment/'.$order->id.'/status')->assertStatus(502);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_an_expired_webhook_signature_is_rejected(): void
    {
        $timestamp = time() - 600;
        $body = '{}';
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'whsec-test');
        $this->assertFalse(app(PayMongoService::class)->verifyWebhook("t={$timestamp},te={$signature},li=", $body));
    }
}
