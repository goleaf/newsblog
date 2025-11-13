<?php

namespace Tests\Feature\Feature\Commands;

use Tests\TestCase;

class MaintenanceModeCommandTest extends TestCase
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
