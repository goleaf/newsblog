<?php

namespace Tests\Feature\Feature\Middleware;

use App\Http\Middleware\RoleMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_user_with_correct_role(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $middleware = new RoleMiddleware;
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('Success');
        }, 'admin');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_blocks_user_with_incorrect_role(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $middleware = new RoleMiddleware;
        $request = Request::create('/test', 'GET');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('You do not have permission to access this resource.');

        $middleware->handle($request, function ($req) {
            return new Response('Success');
        }, 'admin');
    }

    public function test_redirects_unauthenticated_user(): void
    {
        $middleware = new RoleMiddleware;
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('Success');
        }, 'admin');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect(route('login')));
    }

    public function test_allows_user_with_any_of_multiple_roles(): void
    {
        $user = User::factory()->create(['role' => 'editor']);
        $this->actingAs($user);

        $middleware = new RoleMiddleware;
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('Success');
        }, 'admin', 'editor', 'moderator');

        $this->assertEquals(200, $response->getStatusCode());
    }
}
