<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Tests\TestCase;

class ComprehensiveRoleLogoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_can_logout_cleanly(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');

        // Verify protected dashboard is inaccessible
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_admin_can_logout_cleanly(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_sales_can_logout_cleanly(): void
    {
        $user = User::factory()->create();
        $user->assignRole('sales');

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_staff_can_logout_cleanly(): void
    {
        $user = User::factory()->create();
        $user->assignRole('staff');

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_driver_can_logout_cleanly(): void
    {
        $user = User::factory()->create();
        $user->assignRole('driver');

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_logout_is_idempotent_when_already_guest(): void
    {
        $this->assertGuest();

        $response = $this->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_stale_or_mismatched_token_logout_redirects_to_login_gracefully(): void
    {
        $user = User::factory()->create();
        $user->assignRole('sales');

        $this->actingAs($user);

        // Simulate token mismatch exception via handler
        $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $request = \Illuminate\Http\Request::create('/logout', 'POST', ['_token' => 'invalid-token-123']);
        $request->setLaravelSession(app('session')->driver());

        $exception = new TokenMismatchException('CSRF token mismatch.');
        $response = $handler->render($request, $exception);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('login', $response->headers->get('Location'));
    }
}
