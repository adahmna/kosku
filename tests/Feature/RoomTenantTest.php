<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoomTenantTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function owner(): User
    {
        return User::where('email', 'admin@kosku.id')->firstOrFail();
    }

    public function test_owner_can_create_update_and_delete_a_room(): void
    {
        $this->actingAs($this->owner())->post('/kamar', [
            'nomor' => 'K999',
            'tipe' => 'Deluxe',
            'lantai' => 4,
            'harga' => 1500000,
            'luas' => 14,
            'status' => 'Tersedia',
            'fasilitas' => ['AC', 'WiFi'],
            'deskripsi' => 'Kamar uji coba',
        ])->assertRedirect();

        $room = Room::where('nomor', 'K999')->firstOrFail();
        $this->assertSame(['AC', 'WiFi'], $room->fasilitas);

        $this->actingAs($this->owner())->patch('/kamar/'.$room->id, [
            'nomor' => 'K999',
            'tipe' => 'VIP',
            'lantai' => 4,
            'harga' => 1900000,
            'luas' => 16,
            'status' => 'Tersedia',
            'fasilitas' => ['AC', 'WiFi', 'Water Heater'],
            'deskripsi' => 'Diperbarui',
        ])->assertRedirect();

        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'tipe' => 'VIP', 'harga' => 1900000]);

        $this->actingAs($this->owner())->delete('/kamar/'.$room->id)->assertRedirect();
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_room_number_must_be_unique(): void
    {
        $this->actingAs($this->owner())->post('/kamar', [
            'nomor' => 'K101',
            'tipe' => 'Standard',
            'lantai' => 1,
            'harga' => 800000,
            'luas' => 9,
            'status' => 'Tersedia',
            'fasilitas' => [],
        ])->assertSessionHasErrors('nomor');
    }

    public function test_room_with_active_tenant_cannot_be_deleted(): void
    {
        $room = Room::where('nomor', 'K201')->firstOrFail();

        $this->actingAs($this->owner())->delete('/kamar/'.$room->id)->assertRedirect();
        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
    }

    public function test_creating_tenant_computes_end_date_and_occupies_room(): void
    {
        $room = Room::where('nomor', 'K101')->firstOrFail();

        $this->actingAs($this->owner())->post('/penghuni', [
            'nama' => 'Penghuni Baru',
            'nik' => '3271012345678999',
            'hp' => '081200000099',
            'email' => 'baru@mail.com',
            'alamat' => 'Jl. Uji No. 1',
            'room_id' => $room->id,
            'tanggal_masuk' => '2026-01-15',
            'durasi_bulan' => 6,
            'deposit' => 500000,
            'catatan' => '',
            'status' => 'Aktif',
        ])->assertRedirect();

        $this->assertDatabaseHas('tenants', [
            'nama' => 'Penghuni Baru',
            'tanggal_berakhir' => '2026-07-15 00:00:00',
        ]);

        $tenant = Tenant::where('nama', 'Penghuni Baru')->firstOrFail();
        $user = User::where('email', 'baru@mail.com')->firstOrFail();

        $this->assertSame($user->id, $tenant->user_id);
        $this->assertSame('penghuni', $user->role);
        $this->assertTrue(Hash::check('penghunibaruk101', $user->password));
        $this->assertSame('Terisi', $room->fresh()->status);
    }

    public function test_updating_tenant_syncs_login_email_and_password(): void
    {
        $tenant = Tenant::where('nama', 'Budi Santoso')->firstOrFail();
        $user = $tenant->user;

        $this->actingAs($this->owner())->patch('/penghuni/'.$tenant->id, [
            'nama' => $tenant->nama,
            'nik' => $tenant->nik,
            'hp' => $tenant->hp,
            'email' => 'budi.baru@mail.com',
            'password' => 'passwordbaru123',
            'alamat' => $tenant->alamat,
            'room_id' => $tenant->room_id,
            'tanggal_masuk' => $tenant->tanggal_masuk->toDateString(),
            'durasi_bulan' => $tenant->durasi_bulan,
            'deposit' => $tenant->deposit,
            'catatan' => $tenant->catatan,
            'status' => 'Aktif',
        ])->assertRedirect();

        $this->assertSame('budi.baru@mail.com', $user->fresh()->email);
        $this->assertTrue(Hash::check('passwordbaru123', $user->fresh()->password));
    }

    public function test_tenant_cannot_be_assigned_to_an_occupied_room(): void
    {
        $occupied = Room::where('nomor', 'K201')->firstOrFail();

        $this->actingAs($this->owner())->post('/penghuni', [
            'nama' => 'Penghuni Baru',
            'nik' => '3271012345678999',
            'hp' => '081200000099',
            'email' => 'baru@mail.com',
            'alamat' => 'Jl. Uji No. 1',
            'room_id' => $occupied->id,
            'tanggal_masuk' => '2026-01-15',
            'durasi_bulan' => 6,
            'deposit' => 0,
            'catatan' => '',
            'status' => 'Aktif',
        ])->assertSessionHasErrors('room_id');
    }

    public function test_moving_tenant_updates_room_statuses(): void
    {
        $tenant = Tenant::where('nama', 'Budi Santoso')->firstOrFail();
        $oldRoom = $tenant->room;
        $newRoom = Room::where('nomor', 'K101')->firstOrFail();

        $this->actingAs($this->owner())->patch('/penghuni/'.$tenant->id, [
            'nama' => $tenant->nama,
            'nik' => $tenant->nik,
            'hp' => $tenant->hp,
            'email' => $tenant->email,
            'alamat' => $tenant->alamat,
            'room_id' => $newRoom->id,
            'tanggal_masuk' => $tenant->tanggal_masuk->toDateString(),
            'durasi_bulan' => 12,
            'deposit' => 1200000,
            'catatan' => '',
            'status' => 'Aktif',
        ])->assertRedirect();

        $this->assertSame('Tersedia', $oldRoom->fresh()->status);
        $this->assertSame('Terisi', $newRoom->fresh()->status);
        $this->assertSame($newRoom->id, $tenant->fresh()->room_id);
    }

    public function test_deleting_tenant_frees_the_room(): void
    {
        $tenant = Tenant::where('nama', 'Ahmad Fauzi')->firstOrFail();
        $room = $tenant->room;

        $this->actingAs($this->owner())->delete('/penghuni/'.$tenant->id)->assertRedirect();

        $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
        $this->assertSame('Tersedia', $room->fresh()->status);
    }
}
