<?php

namespace Tests\Feature\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsContentGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
