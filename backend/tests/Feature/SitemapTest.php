<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_lists_static_routes_and_product_categories(): void
    {
        Product::factory()->create(['category' => 'Cake']);

        $response = $this->get('/sitemap.xml')->assertOk();

        $response->assertHeader('Content-Type', 'application/xml');

        $xml = $response->getContent();
        $siteUrl = rtrim(config('app.url'), '/');
        $this->assertStringContainsString('<loc>'.$siteUrl.'/</loc>', $xml);
        foreach (['/menu', '/stores', '/franchise', '/custom-cake', '/about', '/contact'] as $path) {
            $this->assertStringContainsString('<loc>'.$siteUrl.$path.'</loc>', $xml);
        }
        $this->assertStringContainsString('<loc>'.$siteUrl.'/menu?category=Cake</loc>', $xml);
    }
}
