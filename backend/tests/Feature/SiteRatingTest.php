<?php

namespace Tests\Feature;

use App\Models\SiteRating;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteRatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_a_one_to_five_star_rating(): void
    {
        $this->postJson(route('site-rating.store'), ['rating' => 5, 'page' => '/'])
            ->assertCreated()
            ->assertJsonPath('message', 'Thanks for your rating!');

        $this->assertDatabaseHas('site_ratings', ['rating' => 5, 'page' => '/', 'user_id' => null]);
    }

    public function test_rating_must_be_between_one_and_five(): void
    {
        $this->postJson(route('site-rating.store'), ['rating' => 6])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rating');

        $this->assertSame(0, SiteRating::count());
    }

    public function test_landing_page_includes_the_rating_prompt(): void
    {
        $this->get('/')->assertOk()->assertSee('id="site-rating-modal"', false);
    }
}
