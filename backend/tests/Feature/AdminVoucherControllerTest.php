<?php

namespace Tests\Feature;

use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminVoucherControllerTest extends TestCase
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
            ->get(route('admin.vouchers.index'))
            ->assertForbidden();
    }

    public function test_editor_sees_the_card_editor_with_vouchers(): void
    {
        Voucher::create(['code' => 'SWEET10', 'type' => 'percent', 'value' => 10, 'active' => true]);

        $this->withSession($this->asUser('editor@bwsuperbakeshop.com'))
            ->get(route('admin.vouchers.index'))
            ->assertOk()
            ->assertSee('Vouchers')
            ->assertSee('SWEET10')
            ->assertSee('Save changes');
    }

    public function test_bulk_save_uppercases_codes_and_zeroes_freedel_values(): void
    {
        $this->withSession($this->asUser('editor@bwsuperbakeshop.com'))
            ->post(route('admin.vouchers.sync'), [
                'vouchers' => [
                    ['id' => '', 'code' => '  sweet10 ', 'type' => 'percent', 'value' => 10, 'label' => '10% off', 'active' => '1'],
                    ['id' => '', 'code' => 'freedel', 'type' => 'freedel', 'value' => 50, 'active' => '1'],
                    ['id' => '', 'code' => '   '], // untouched blank card
                ],
                'originalIds' => [],
            ])
            ->assertRedirect(route('admin.vouchers.index'));

        $this->assertSame(2, Voucher::count());
        $this->assertDatabaseHas('vouchers', ['code' => 'SWEET10', 'type' => 'percent', 'active' => true]);
        $this->assertEquals(0.0, Voucher::where('code', 'FREEDEL')->first()->value);
    }

    public function test_unchecking_active_deactivates_and_expiry_is_saved(): void
    {
        $voucher = Voucher::create(['code' => 'OLD', 'type' => 'amount', 'value' => 50, 'active' => true]);

        $this->withSession($this->asUser('bw.redeem@gmail.com'))
            ->post(route('admin.vouchers.sync'), [
                'vouchers' => [[
                    'id' => $voucher->id,
                    'code' => 'OLD',
                    'type' => 'amount',
                    'value' => 50,
                    'expires_at' => '2026-12-25',
                    // no `active` key — an unchecked toggle submits nothing
                ]],
                'originalIds' => [$voucher->id],
            ])
            ->assertRedirect(route('admin.vouchers.index'));

        $fresh = $voucher->fresh();
        $this->assertFalse($fresh->active);
        $this->assertSame('2026-12-25', $fresh->expires_at->format('Y-m-d'));
    }

    public function test_a_voucher_removed_from_the_grid_is_deleted(): void
    {
        $kept = Voucher::create(['code' => 'KEEP', 'type' => 'percent', 'value' => 5, 'active' => true]);
        $removed = Voucher::create(['code' => 'BYE', 'type' => 'percent', 'value' => 5, 'active' => true]);

        $this->withSession($this->asUser('bw.redeem@gmail.com'))
            ->post(route('admin.vouchers.sync'), [
                'vouchers' => [['id' => $kept->id, 'code' => 'KEEP', 'type' => 'percent', 'value' => 5, 'active' => '1']],
                'originalIds' => [$kept->id, $removed->id],
            ])
            ->assertRedirect(route('admin.vouchers.index'));

        $this->assertDatabaseMissing('vouchers', ['id' => $removed->id]);
        $this->assertDatabaseHas('vouchers', ['id' => $kept->id]);
    }
}
