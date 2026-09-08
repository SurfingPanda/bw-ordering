<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN = 'bw.redeem@gmail.com';
    private const EDITOR = 'editor@bwsuperbakeshop.com';
    private const CUSTOMER = 'customer@example.com';

    private function sess(string $email): array
    {
        return [
            'supabase_user' => ['id' => 'test-id', 'email' => $email, 'name' => 'Test User'],
            'supabase_token_expires_at' => now()->addHour()->timestamp,
        ];
    }

    public function test_admins_and_editors_can_open_the_audit_log(): void
    {
        foreach ([self::ADMIN, self::EDITOR] as $email) {
            $this->withSession($this->sess($email))
                ->get(route('admin.audit-log'))
                ->assertOk()
                ->assertSee('Audit Log');
        }
    }

    public function test_a_customer_is_forbidden(): void
    {
        $this->withSession($this->sess(self::CUSTOMER))
            ->get(route('admin.audit-log'))
            ->assertForbidden();
    }

    public function test_the_nav_link_shows_for_editors_and_admins(): void
    {
        foreach ([self::ADMIN, self::EDITOR] as $email) {
            $this->withSession($this->sess($email))
                ->get(route('admin.content'))
                ->assertOk()
                ->assertSee(route('admin.audit-log'), false);
        }
    }

    public function test_a_staff_mutation_records_an_entry(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_name' => 'Cara Customer',
            'customer_email' => 'customer@example.com',
            'items' => [['product_id' => 1, 'name' => 'Ube Chiffon Cake', 'qty' => 1, 'price' => 720]],
            'subtotal' => 720, 'total' => 806.40, 'status' => 'pending',
        ]);

        $this->withSession($this->sess(self::ADMIN))
            ->post("/admin/orders/{$order->id}/status", ['status' => 'preparing'])
            ->assertRedirect();

        $log = AuditLog::firstWhere('action', 'order.status_updated');
        $this->assertNotNull($log);
        $this->assertSame(self::ADMIN, $log->actor_email);
        $this->assertSame('admin', $log->actor_role);
        $this->assertSame(['from' => 'pending', 'to' => 'preparing'], $log->meta);
        $this->assertStringContainsString(strtoupper(substr($order->id, 0, 8)), (string) $log->target);
    }

    public function test_entries_can_be_filtered_by_action(): void
    {
        AuditLog::create(['actor_email' => self::ADMIN, 'action' => 'content.updated', 'target' => 'Section: nav']);
        AuditLog::create(['actor_email' => self::ADMIN, 'action' => 'voucher.saved', 'target' => 'Voucher grid']);

        $this->withSession($this->sess(self::ADMIN))
            ->get(route('admin.audit-log', ['action' => 'voucher.saved']))
            ->assertOk()
            ->assertSee('Voucher grid')
            ->assertDontSee('Section: nav');
    }
}
