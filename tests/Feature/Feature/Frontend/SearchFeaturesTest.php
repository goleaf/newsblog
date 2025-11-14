<?php

namespace Tests\Feature\Feature\Frontend;

use Tests\TestCase;

class SearchFeaturesTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
