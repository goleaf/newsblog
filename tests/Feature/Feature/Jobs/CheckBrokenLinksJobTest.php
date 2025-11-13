<?php

namespace Tests\Feature\Feature\Jobs;

use Tests\TestCase;

class CheckBrokenLinksJobTest extends TestCase
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
