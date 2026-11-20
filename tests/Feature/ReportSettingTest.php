<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSettingTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function owner(): User
    {
        return User::where('email', 'admin@kosku.id')->firstOrFail();
    }

    public function test_owner_can_view_reports_and_filter_by_year(): void
    {
        $this->actingAs($this->owner())
            ->get('/laporan?tahun='.now()->year)
            ->assertOk()
            ->assertSee('Laporan Keuangan')
            ->assertSee('Total Pemasukan');
    }

    public function test_owner_can_export_report_as_csv(): void
    {
        $response = $this->actingAs($this->owner())->get('/laporan/export?tahun='.now()->year);

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Penghuni,Kamar,Periode', $response->streamedContent());
    }

    public function test_owner_can_update_profile_and_billing_settings(): void
    {
        $this->actingAs($this->owner())->patch('/pengaturan/profil', [
            'nama_kos' => 'Kos Baru',
            'no_hp' => '081111111111',
            'alamat' => 'Jl. Baru No. 1',
            'email' => 'baru@kosku.id',
            'deskripsi' => 'Deskripsi baru',
        ])->assertRedirect();

        $this->assertSame('Kos Baru', Setting::get('nama_kos'));

        $this->actingAs($this->owner())->patch('/pengaturan/tagihan', [
            'tgl_generate' => 2,
            'jatuh_tempo' => 12,
            'denda_persen' => 2,
            'denda_flat' => 15000,
            'pengingat_otomatis' => 1,
        ])->assertRedirect();

        $this->assertSame('2', Setting::get('tgl_generate'));
        $this->assertSame('0', Setting::get('notifikasi_wa'));
    }

    public function test_pemilik_can_add_and_toggle_a_user(): void
    {
        $this->actingAs($this->owner())->post('/pengaturan/user', [
            'name' => 'Pemilik Baru',
            'email' => 'baru-pemilik@kosku.id',
            'password' => 'password123',
            'role' => 'pemilik',
        ])->assertRedirect();

        $newUser = User::where('email', 'baru-pemilik@kosku.id')->firstOrFail();
        $this->assertSame('pemilik', $newUser->role);
        $this->assertTrue($newUser->aktif);

        $this->actingAs($this->owner())->patch('/pengaturan/user/'.$newUser->id.'/toggle')->assertRedirect();
        $this->assertFalse($newUser->fresh()->aktif);
    }

    public function test_owner_cannot_deactivate_own_account(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->patch('/pengaturan/user/'.$owner->id.'/toggle')
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue($owner->fresh()->aktif);
    }

    public function test_adding_penghuni_account_requires_matching_tenant(): void
    {
        $this->actingAs($this->owner())->post('/pengaturan/user', [
            'name' => 'Tanpa Tenant',
            'email' => 'tanpa-tenant@kosku.id',
            'password' => 'password123',
            'role' => 'penghuni',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'tanpa-tenant@kosku.id']);
    }

    public function test_penghuni_account_can_be_linked_to_existing_tenant(): void
    {
        $tenant = Tenant::create([
            'nama' => 'Penghuni Akun',
            'nik' => '3271012345678888',
            'hp' => '081277777777',
            'email' => 'akun@kosku.id',
            'alamat' => 'Jl. Akun No. 2',
            'tanggal_masuk' => '2026-01-10',
            'tanggal_berakhir' => '2026-04-10',
            'durasi_bulan' => 3,
            'deposit' => 0,
            'status' => 'Aktif',
        ]);

        $this->actingAs($this->owner())->post('/pengaturan/user', [
            'name' => 'Penghuni Akun',
            'email' => 'akun@kosku.id',
            'password' => 'password123',
            'role' => 'penghuni',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $user = User::where('email', 'akun@kosku.id')->firstOrFail();
        $this->assertSame($user->id, $tenant->fresh()->user_id);

        $this->actingAs($user)->get('/saya/tagihan')->assertOk();
    }

    public function test_deleting_tenant_deactivates_linked_account(): void
    {
        $tenant = Tenant::where('nama', 'Budi Santoso')->firstOrFail();
        $user = $tenant->user;

        $this->actingAs($this->owner())->delete('/penghuni/'.$tenant->id)->assertRedirect();

        $this->assertFalse($user->fresh()->aktif);
    }
}
