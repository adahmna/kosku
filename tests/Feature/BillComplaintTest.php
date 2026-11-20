<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Complaint;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BillComplaintTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function owner(): User
    {
        return User::where('email', 'admin@kosku.id')->firstOrFail();
    }

    private function penghuni(): User
    {
        return User::where('email', 'budi@mail.com')->firstOrFail();
    }

    public function test_owner_can_create_a_bill_and_total_is_calculated(): void
    {
        $tenant = Tenant::where('nama', 'Eko Widodo')->firstOrFail();
        $tenant->update(['status' => 'Aktif', 'room_id' => Room::where('nomor', 'K101')->firstOrFail()->id]);

        $this->actingAs($this->owner())->post('/tagihan', [
            'tenant_id' => $tenant->id,
            'bulan' => 12,
            'tahun' => 2026,
            'sewa' => 800000,
            'listrik' => 90000,
            'air' => 30000,
            'tambahan' => 25000,
            'ket_tambahan' => 'Denda',
            'jatuh_tempo' => '2026-12-10',
            'status' => 'Lunas',
            'tanggal_bayar' => '2026-12-05',
            'metode' => 'QRIS',
        ])->assertRedirect();

        $this->assertDatabaseHas('bills', [
            'tenant_id' => $tenant->id,
            'bulan' => 12,
            'tahun' => 2026,
            'total' => 945000,
            'status' => 'Lunas',
            'metode' => 'QRIS',
        ]);
    }

    public function test_bill_period_must_be_unique_per_tenant(): void
    {
        $bill = Bill::firstOrFail();

        $this->actingAs($this->owner())->post('/tagihan', [
            'tenant_id' => $bill->tenant_id,
            'bulan' => $bill->bulan,
            'tahun' => $bill->tahun,
            'sewa' => 1,
            'listrik' => 0,
            'air' => 0,
            'tambahan' => 0,
            'jatuh_tempo' => '2026-12-10',
            'status' => 'Belum Lunas',
        ])->assertSessionHasErrors('bulan');
    }

    public function test_generate_monthly_bills_creates_missing_bills_and_skips_existing(): void
    {
        $now = Carbon::now();
        $tenant = Tenant::where('nama', 'Budi Santoso')->firstOrFail();

        Bill::where('tenant_id', $tenant->id)
            ->where('bulan', $now->month)
            ->where('tahun', $now->year)
            ->delete();

        $response = $this->actingAs($this->owner())->post('/tagihan/generate');
        $response->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('bills', [
            'tenant_id' => $tenant->id,
            'bulan' => $now->month,
            'tahun' => $now->year,
            'status' => 'Belum Lunas',
        ]);

        $countBefore = Bill::count();
        $this->actingAs($this->owner())->post('/tagihan/generate')->assertRedirect();
        $this->assertSame($countBefore, Bill::count());
    }

    public function test_owner_can_update_and_delete_a_bill(): void
    {
        $bill = Bill::where('status', 'Belum Lunas')->firstOrFail();

        $this->actingAs($this->owner())->patch('/tagihan/'.$bill->id, [
            'tenant_id' => $bill->tenant_id,
            'bulan' => $bill->bulan,
            'tahun' => $bill->tahun,
            'sewa' => $bill->sewa,
            'listrik' => $bill->listrik,
            'air' => $bill->air,
            'tambahan' => 0,
            'jatuh_tempo' => $bill->jatuh_tempo->toDateString(),
            'status' => 'Lunas',
            'metode' => 'Cash',
        ])->assertRedirect();

        $this->assertSame('Lunas', $bill->fresh()->status);
        $this->assertNotNull($bill->fresh()->tanggal_bayar);

        $this->actingAs($this->owner())->delete('/tagihan/'.$bill->id)->assertRedirect();
        $this->assertDatabaseMissing('bills', ['id' => $bill->id]);
    }

    public function test_owner_complaint_update_appends_timeline_and_completion_date(): void
    {
        $complaint = Complaint::where('status', 'Baru')->firstOrFail();

        $this->actingAs($this->owner())->patch('/pengaduan/'.$complaint->id, [
            'tenant_id' => $complaint->tenant_id,
            'kategori' => $complaint->kategori,
            'prioritas' => 'Tinggi',
            'judul' => $complaint->judul,
            'deskripsi' => $complaint->deskripsi,
            'status' => 'Selesai',
            'target_selesai' => '',
            'catatan' => 'Sudah ditangani',
        ])->assertRedirect();

        $complaint->refresh();
        $this->assertSame('Selesai', $complaint->status);
        $this->assertNotNull($complaint->tanggal_selesai);
        $this->assertDatabaseHas('complaint_events', ['complaint_id' => $complaint->id, 'label' => 'Status diubah ke Selesai']);
    }

    public function test_penghuni_can_create_complaint_and_only_view_own_detail(): void
    {
        $user = $this->penghuni();

        $this->actingAs($user)->post('/saya/pengaduan', [
            'kategori' => 'Kebersihan',
            'prioritas' => 'Rendah',
            'judul' => 'Sampah menumpuk',
            'deskripsi' => 'Sampah di koridor belum diangkut.',
        ])->assertRedirect();

        $this->assertDatabaseHas('complaints', [
            'tenant_id' => $user->tenant->id,
            'judul' => 'Sampah menumpuk',
            'status' => 'Baru',
        ]);

        $own = Complaint::where('judul', 'Sampah menumpuk')->firstOrFail();
        $other = Complaint::where('tenant_id', '!=', $user->tenant->id)->firstOrFail();

        $this->actingAs($user)->getJson('/pengaduan/'.$own->id)->assertOk()->assertJsonPath('judul', 'Sampah menumpuk');
        $this->actingAs($user)->getJson('/pengaduan/'.$other->id)->assertForbidden();
    }

    public function test_penghuni_can_upload_payment_proof_for_own_bill_only(): void
    {
        Storage::fake('public');
        $user = $this->penghuni();
        $own = Bill::where('tenant_id', $user->tenant->id)->firstOrFail();
        $other = Bill::where('tenant_id', '!=', $user->tenant->id)->firstOrFail();

        $this->actingAs($user)->post('/saya/tagihan/'.$own->id.'/bukti', [
            'bukti_bayar' => UploadedFile::fake()->create('bukti.jpg', 100, 'image/jpeg'),
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertNotNull($own->fresh()->bukti_bayar);
        Storage::disk('public')->assertExists($own->fresh()->bukti_bayar);

        $this->actingAs($user)->post('/saya/tagihan/'.$other->id.'/bukti', [
            'bukti_bayar' => UploadedFile::fake()->create('bukti.jpg', 100, 'image/jpeg'),
        ])->assertForbidden();
    }

    public function test_penghuni_can_update_profile(): void
    {
        $user = $this->penghuni();

        $this->actingAs($user)->patch('/saya/profil', [
            'hp' => '081999999999',
            'email' => 'budi.baru@mail.com',
        ])->assertRedirect();

        $this->assertDatabaseHas('tenants', [
            'id' => $user->tenant->id,
            'hp' => '081999999999',
            'email' => 'budi.baru@mail.com',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'budi.baru@mail.com',
        ]);
    }
}
