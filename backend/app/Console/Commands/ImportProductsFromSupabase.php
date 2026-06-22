<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\SiteContent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

// One-time migration helper: copy the products catalogue out of Supabase into
// the local MySQL `products` table, preserving ids. The products table is
// world-readable in Supabase, so the public anon key is enough.
//
//   php artisan products:import-from-supabase
//
// Requires SUPABASE_URL and SUPABASE_ANON_KEY in backend/.env.
class ImportProductsFromSupabase extends Command
{
    protected $signature = 'products:import-from-supabase {--fresh : Truncate the products table first}';

    protected $description = 'Import products from the Supabase REST API into MySQL';

    public function handle(): int
    {
        $url = rtrim((string) config('supabase.url'), '/');
        $key = (string) config('supabase.anon_key');

        if (! $url || ! $key) {
            $this->error('Set SUPABASE_URL and SUPABASE_ANON_KEY in backend/.env first.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            Product::query()->delete();
            $this->warn('Cleared existing products.');
        }

        $resp = Http::withHeaders([
            'apikey' => $key,
            'Authorization' => "Bearer {$key}",
        ])->get("{$url}/rest/v1/products", ['select' => '*']);

        if ($resp->failed()) {
            $this->error('Supabase request failed: '.$resp->status().' '.$resp->body());

            return self::FAILURE;
        }

        $rows = $resp->json();
        $count = 0;
        foreach ($rows as $r) {
            Product::updateOrCreate(
                ['id' => $r['id']],
                [
                    'name' => $r['name'] ?? 'Untitled',
                    'description' => $r['description'] ?? null,
                    'price' => $r['price'] ?? 0,
                    'original_price' => $r['original_price'] ?? null,
                    'image_path' => $r['image_path'] ?? null,
                    'features' => is_array($r['features'] ?? null) ? $r['features'] : [],
                    'calories' => $r['calories'] ?? null,
                    'is_featured' => $r['is_featured'] ?? false,
                    'status' => $r['status'] ?? null,
                    'category' => $r['category'] ?? null,
                    'archived_at' => $r['archived_at'] ?? null,
                ]
            );
            $count++;
        }

        $this->info("Imported {$count} products from Supabase.");

        // Also bring over the landing/franchise CMS blob (single row, id = 1).
        $cms = Http::withHeaders([
            'apikey' => $key,
            'Authorization' => "Bearer {$key}",
        ])->get("{$url}/rest/v1/site_content", ['select' => 'data', 'id' => 'eq.1']);

        if ($cms->ok() && ! empty($cms->json()[0]['data'])) {
            SiteContent::updateOrCreate(['id' => 1], ['data' => $cms->json()[0]['data']]);
            $this->info('Imported landing CMS content from Supabase.');
        } else {
            $this->line('No saved CMS content to import (using defaults).');
        }

        return self::SUCCESS;
    }
}
