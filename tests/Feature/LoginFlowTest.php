<?php

namespace Tests\Feature;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LoginFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function login(string $username, string $password)
    {
        return $this->post('/login', [
            'username' => $username,
            'password' => $password,
        ]);
    }

    public function test_sales_login_and_access(): void
    {
        $r = $this->login('karyawan', 'password');
        $r->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs(\App\Models\User::where('username', 'karyawan')->first());

        $this->get('/dashboard')->assertOk()->assertSee('Kunjungan Hari Ini');
        $this->get('/route')->assertOk();
        $this->get('/visit')->assertOk();
    }

    public function test_staff_login_and_access(): void
    {
        $r = $this->login('staff', 'password123');
        $r->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs(\App\Models\User::where('username', 'staff')->first());

        $dash = $this->get('/dashboard');
        $dash->assertOk()->assertSee('Presensi Hari Ini')->assertSee('Laporan Presensi Saya');

        $this->get('/route')->assertRedirect('/dashboard');
        $this->get('/visit')->assertRedirect('/dashboard');
        $this->get('/admin/stores')->assertRedirect('/dashboard');
        $this->get('/attendance')->assertOk();
        $this->get('/profile')->assertOk();
    }

    public function test_admin_login_and_access(): void
    {
        $r = $this->login('admin', 'password');
        $r->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs(\App\Models\User::where('username', 'admin')->first());

        $this->get('/dashboard')->assertOk();
        $this->get('/admin/users')->assertOk();
        $this->get('/admin/stores')->assertOk();
    }

    public function test_super_admin_login_and_access(): void
    {
        $r = $this->login('superadmin', 'password');
        $r->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs(\App\Models\User::where('username', 'superadmin')->first());

        $this->get('/dashboard')->assertOk();
        $this->get('/admin/users')->assertOk();
        $this->get('/admin/stores')->assertOk();
    }

    public function test_karyawan_users_became_sales_and_keep_access(): void
    {
        $karyawan = \App\Models\User::where('username', 'karyawan')->first();
        $this->assertTrue($karyawan->hasRole('sales'));
        $this->assertFalse($karyawan->hasRole('karyawan'));

        $this->actingAs($karyawan)->get('/route')->assertOk();
        $this->actingAs($karyawan)->get('/visit')->assertOk();
        $this->actingAs($karyawan)->get('/dashboard')->assertOk()->assertSee('Kunjungan Hari Ini');
    }

    public function test_login_without_remember_keeps_no_remember_token(): void
    {
        $user = \App\Models\User::where('username', 'karyawan')->first();
        $this->assertNull($user->remember_token);

        $r = $this->login('karyawan', 'password');
        $r->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertNull($user->remember_token);
    }

    public function test_remember_me_wires_laravel_remember_mechanism(): void
    {
        $user = \App\Models\User::where('username', 'karyawan')->first();
        $this->assertNull($user->remember_token);

        $r = $this->post('/login', [
            'username' => 'karyawan',
            'password' => 'password',
            'remember' => '1',
        ]);
        $r->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertNotNull($user->remember_token);

        $rememberCookie = collect($r->headers->getCookies())
            ->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));
        $this->assertNotNull($rememberCookie, 'remember cookie not set');
        $this->assertFalse($rememberCookie->isCleared());

        $expiry = $rememberCookie->getExpiresTime();
        $this->assertGreaterThan(now()->addDays(29)->timestamp, $expiry);
        $this->assertLessThanOrEqual(now()->addDays(31)->timestamp, $expiry);
    }

    public function test_remember_cookie_authenticates_user_on_fresh_request(): void
    {
        $user = \App\Models\User::where('username', 'karyawan')->first();

        $r = $this->post('/login', [
            'username' => 'karyawan',
            'password' => 'password',
            'remember' => '1',
        ]);
        $r->assertRedirect('/dashboard');

        $rememberCookie = collect($r->headers->getCookies())
            ->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));
        $this->assertNotNull($rememberCookie);

        // Simulate a browser fully closed + reopened: wipe the session and auth state,
        // keeping only the "remember" cookie the browser stored.
        $value = $rememberCookie->getValue();
        $plain = \Illuminate\Support\Facades\Crypt::decrypt($value, false);
        $prefix = \Illuminate\Cookie\CookieValuePrefix::create($rememberCookie->getName(), app('encrypter')->getKey());
        if (is_string($plain) && str_starts_with($plain, $prefix)) {
            $plain = substr($plain, strlen($prefix));
        }

        Auth::forgetGuards();
        $this->flushSession();
        $this->assertGuest();

        // Replay the cookie as the browser would: the test client encrypts it on
        // the way out and EncryptCookies decrypts it on the way in (same as a real
        // browser/server roundtrip), so the guard reads the plain recaller value.
        $this->withCookie($rememberCookie->getName(), $plain);
        $this->get('/dashboard')->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_logout_invalidates_remember_me(): void
    {
        $user = \App\Models\User::where('username', 'karyawan')->first();

        $r = $this->post('/login', [
            'username' => 'karyawan',
            'password' => 'password',
            'remember' => '1',
        ]);
        $r->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $tokenBefore = $user->refresh()->remember_token;
        $this->assertNotNull($tokenBefore);

        $rememberCookie = collect($r->headers->getCookies())
            ->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));
        $this->assertNotNull($rememberCookie);

        $this->withCookie($rememberCookie->getName(), $rememberCookie->getValue());
        $r2 = $this->post('/logout');
        $r2->assertRedirect('/login');
        $this->assertGuest();

        $forgotCookie = collect($r2->headers->getCookies())
            ->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));
        $this->assertNotNull($forgotCookie, 'remember cookie should be cleared on logout');
        $this->assertTrue($forgotCookie->isCleared());

        $user->refresh();
        $this->assertNotSame($tokenBefore, $user->remember_token);
    }
}
