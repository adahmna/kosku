<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_login_screen_can_be_rendered_without_demo_credentials(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('KosKu')
            ->assertDontSee('Demo Credentials')
            ->assertDontSee('demo1234')
            ->assertDontSee('pengelola@kosku.id')
            ->assertDontSee('penghuni@kosku.id');
    }

    public function test_pemilik_can_login_and_is_redirected_to_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@kosku.id',
            'password' => 'demo1234',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_penghuni_can_login_using_their_registered_email(): void
    {
        $tenant = Tenant::where('nama', 'Budi Santoso')->firstOrFail();

        $this->post('/login', [
            'email' => $tenant->email,
            'password' => $tenant->defaultPassword(),
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($tenant->user);
    }

    public function test_penghuni_without_matching_tenant_cannot_login(): void
    {
        User::create([
            'name' => 'Penghuni Tanpa Data',
            'email' => 'tanpa-data@kosku.id',
            'password' => 'password123',
            'role' => 'penghuni',
            'aktif' => true,
        ]);

        $this->post('/login', [
            'email' => 'tanpa-data@kosku.id',
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_penghuni_with_inactive_tenant_cannot_login(): void
    {
        $tenant = Tenant::where('nama', 'Eko Widodo')->firstOrFail();
        $tenant->user->update(['aktif' => true]);

        $this->post('/login', [
            'email' => $tenant->email,
            'password' => $tenant->defaultPassword(),
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $this->post('/login', [
            'email' => 'admin@kosku.id',
            'password' => 'salah',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::where('email', 'admin@kosku.id')->firstOrFail();
        $user->update(['aktif' => false]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'demo1234',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::where('email', 'admin@kosku.id')->firstOrFail();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_role_based_access_is_enforced(): void
    {
        $penghuni = User::where('email', 'budi@mail.com')->firstOrFail();
        $pemilik = User::where('email', 'admin@kosku.id')->firstOrFail();

        $this->actingAs($penghuni)->get('/kamar')->assertForbidden();
        $this->actingAs($penghuni)->get('/laporan')->assertForbidden();
        $this->actingAs($pemilik)->get('/laporan')->assertOk();
        $this->actingAs($pemilik)->get('/pengaturan')->assertOk();
        $this->actingAs($pemilik)->get('/kamar')->assertOk();
        $this->actingAs($pemilik)->get('/saya/tagihan')->assertForbidden();
    }

    public function test_inactive_user_with_active_session_is_blocked(): void
    {
        $user = User::where('email', 'admin@kosku.id')->firstOrFail();
        $user->update(['aktif' => false]);

        $this->actingAs($user)->get('/dashboard')->assertForbidden();
        $this->actingAs($user)->post('/logout')->assertForbidden();
    }
}
