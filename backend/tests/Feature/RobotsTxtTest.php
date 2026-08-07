<?php

namespace Tests\Feature;

use Tests\TestCase;

// A static file in public/ — Laravel's test HTTP kernel doesn't serve static
// assets (that's the webserver's job in production, or artisan serve's PHP
// built-in server locally), so this reads the committed file directly rather
// than going through $this->get().
class RobotsTxtTest extends TestCase
{
    public function test_robots_txt_points_at_the_sitemap(): void
    {
        $contents = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Sitemap:', $contents);
        $this->assertStringContainsString('sitemap.xml', $contents);
    }
}
