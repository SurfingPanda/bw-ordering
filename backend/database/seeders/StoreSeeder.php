<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Seed the bw Superbakeshop branches used by the store locator.
     */
    public function run(): void
    {
        $stores = [
            ['name' => 'BW Superbakeshop — Commonwealth', 'region' => 'Metro Manila', 'address' => '123 Commonwealth Ave, Quezon City', 'hours' => '6:00 AM – 9:00 PM', 'phone' => '(02) 8123 4567', 'latitude' => 14.6970, 'longitude' => 121.0830],
            ['name' => 'BW Superbakeshop — Makati', 'region' => 'Metro Manila', 'address' => '88 Ayala Ave, Makati City', 'hours' => '7:00 AM – 10:00 PM', 'phone' => '(02) 8234 5678', 'latitude' => 14.5560, 'longitude' => 121.0244],
            ['name' => 'BW Superbakeshop — Pasig', 'region' => 'Metro Manila', 'address' => 'Ortigas Center, Pasig City', 'hours' => '6:30 AM – 9:30 PM', 'phone' => '(02) 8345 6789', 'latitude' => 14.5870, 'longitude' => 121.0610],
            ['name' => 'BW Superbakeshop — Pampanga', 'region' => 'Luzon', 'address' => 'MacArthur Hwy, San Fernando, Pampanga', 'hours' => '6:00 AM – 9:00 PM', 'phone' => '(045) 961 2345', 'latitude' => 15.0286, 'longitude' => 120.6898],
            ['name' => 'BW Superbakeshop — Baguio', 'region' => 'Luzon', 'address' => 'Session Rd, Baguio City', 'hours' => '7:00 AM – 9:00 PM', 'phone' => '(074) 442 1234', 'latitude' => 16.4120, 'longitude' => 120.5960],
            ['name' => 'BW Superbakeshop — Cebu', 'region' => 'Visayas', 'address' => 'Osmeña Blvd, Cebu City', 'hours' => '6:30 AM – 10:00 PM', 'phone' => '(032) 255 6789', 'latitude' => 10.3110, 'longitude' => 123.8930],
            ['name' => 'BW Superbakeshop — Iloilo', 'region' => 'Visayas', 'address' => 'Diversion Rd, Mandurriao, Iloilo City', 'hours' => '7:00 AM – 9:00 PM', 'phone' => '(033) 320 4567', 'latitude' => 10.7100, 'longitude' => 122.5510],
            ['name' => 'BW Superbakeshop — Davao', 'region' => 'Mindanao', 'address' => 'C.M. Recto St, Davao City', 'hours' => '6:00 AM – 9:30 PM', 'phone' => '(082) 224 7890', 'latitude' => 7.0707, 'longitude' => 125.6130],
            ['name' => 'BW Superbakeshop — Cagayan de Oro', 'region' => 'Mindanao', 'address' => 'Corrales Ave, Cagayan de Oro', 'hours' => '7:00 AM – 9:00 PM', 'phone' => '(088) 856 3456', 'latitude' => 8.4790, 'longitude' => 124.6460],
        ];

        foreach ($stores as $store) {
            Store::updateOrCreate(
                ['name' => $store['name']],
                $store
            );
        }
    }
}
