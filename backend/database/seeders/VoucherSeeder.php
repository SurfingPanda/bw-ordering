<?php

namespace Database\Seeders;

use App\Models\Voucher;
use Illuminate\Database\Seeder;

class VoucherSeeder extends Seeder
{
    public function run(): void
    {
        // Mirrors the demo codes the frontend shows (src/pages/Menu.jsx VOUCHERS).
        $vouchers = [
            ['code' => 'BW10', 'type' => 'percent', 'value' => 10, 'label' => '10% off'],
            ['code' => 'SAVE50', 'type' => 'amount', 'value' => 50, 'label' => '₱50 off'],
            ['code' => 'FREEDEL', 'type' => 'freedel', 'value' => 0, 'label' => 'Free delivery'],
        ];

        foreach ($vouchers as $v) {
            Voucher::updateOrCreate(['code' => $v['code']], $v + ['active' => true]);
        }
    }
}
