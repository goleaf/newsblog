<?php

namespace Tests\Feature\Feature\Commands;

use Tests\TestCase;

class MonitorNovaPerformanceCommandTest extends TestCase
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
