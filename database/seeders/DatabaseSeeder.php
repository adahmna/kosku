<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\Complaint;
use App\Models\Room;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = collect([
            ['K101', 'Standard', 1, 800000, 9, ['Lemari', 'Kasur', 'Meja Belajar'], 'Tersedia', 'Kamar standar nyaman, cocok untuk mahasiswa.'],
            ['K102', 'Standard', 1, 800000, 9, ['Lemari', 'Kasur', 'Meja Belajar', 'WiFi'], 'Tersedia', 'Kamar standar dengan WiFi.'],
            ['K103', 'Standard', 1, 800000, 9, ['Lemari', 'Kasur'], 'Tersedia', 'Kamar standar ekonomis.'],
            ['K201', 'Deluxe', 2, 1200000, 12, ['AC', 'WiFi', 'Kamar Mandi Dalam', 'Lemari', 'Kasur', 'Meja Belajar'], 'Terisi', 'Kamar deluxe dengan AC dan kamar mandi dalam.'],
            ['K202', 'Deluxe', 2, 1200000, 12, ['AC', 'WiFi', 'Kamar Mandi Dalam', 'Lemari', 'Kasur'], 'Terisi', 'Kamar deluxe lantai 2.'],
            ['K203', 'Deluxe', 2, 1200000, 12, ['AC', 'WiFi', 'Kamar Mandi Dalam', 'Kasur', 'Meja Belajar'], 'Terisi', 'Kamar deluxe terang dan luas.'],
            ['K204', 'Deluxe', 2, 1200000, 12, ['AC', 'WiFi', 'Kamar Mandi Dalam', 'Lemari', 'Kasur'], 'Terisi', 'Kamar deluxe pojok.'],
            ['K301', 'VIP', 3, 1800000, 16, ['AC', 'WiFi', 'Kamar Mandi Dalam', 'Lemari', 'Kasur', 'Meja Belajar', 'Water Heater'], 'Terisi', 'Kamar VIP lengkap dengan water heater.'],
            ['K302', 'VIP', 3, 1800000, 16, ['AC', 'WiFi', 'Kamar Mandi Dalam', 'Lemari', 'Kasur', 'Water Heater'], 'Terisi', 'Kamar VIP dengan view kota.'],
            ['K303', 'VIP', 3, 1800000, 16, ['AC', 'WiFi', 'Kamar Mandi Dalam', 'Water Heater'], 'Dalam Perawatan', 'Sedang dalam renovasi cat & AC.'],
        ])->mapWithKeys(function ($row) {
            [$nomor, $tipe, $lantai, $harga, $luas, $fasilitas, $status, $deskripsi] = $row;
            $room = Room::create(compact('nomor', 'tipe', 'lantai', 'harga', 'luas', 'fasilitas', 'status', 'deskripsi'));

            return [$nomor => $room];
        });

        $now = Carbon::now();
        $tenantRows = [
            ['Budi Santoso', '3271012345678901', '081234567801', 'budi@mail.com', 'Jl. Merdeka No. 12, Bandung', 'K201', 12, 1200000, 'Mahasiswa', 'Aktif', 8],
            ['Siti Rahayu', '3271012345678902', '081234567802', 'siti@mail.com', 'Jl. Sudirman No. 5, Jakarta', 'K202', 12, 1200000, 'Karyawan swasta', 'Aktif', 7],
            ['Ahmad Fauzi', '3271012345678903', '081234567803', 'ahmad@mail.com', 'Jl. Ahmad Yani No. 23, Surabaya', 'K203', 6, 1200000, '', 'Aktif', 4],
            ['Dewi Lestari', '3271012345678904', '081234567804', 'dewi@mail.com', 'Jl. Diponegoro No. 8, Yogyakarta', 'K204', 12, 1200000, 'Pekerja remote', 'Aktif', 6],
            ['Reza Pratama', '3271012345678905', '081234567805', 'reza@mail.com', 'Jl. Pahlawan No. 17, Semarang', 'K301', 12, 1800000, '', 'Aktif', 5],
            ['Nurul Hidayah', '3271012345678906', '081234567806', 'nurul@mail.com', 'Jl. Gatot Subroto No. 3, Medan', 'K302', 6, 1800000, 'Karyawan BUMN', 'Aktif', 3],
            ['Eko Widodo', '3271012345678907', '081234567807', 'eko@mail.com', 'Jl. Veteran No. 9, Solo', null, 12, 0, 'Waiting list untuk kamar VIP', 'Tidak Aktif', 2],
        ];

        $tenants = collect($tenantRows)->mapWithKeys(function ($row) use ($rooms, $now) {
            [$nama, $nik, $hp, $email, $alamat, $roomNomor, $durasi, $deposit, $catatan, $status, $monthsAgo] = $row;
            $masuk = $now->copy()->startOfMonth()->subMonths($monthsAgo);
            $tenant = Tenant::create([
                'room_id' => $roomNomor ? $rooms[$roomNomor]->id : null,
                'nama' => $nama,
                'nik' => $nik,
                'hp' => $hp,
                'email' => $email,
                'alamat' => $alamat,
                'tanggal_masuk' => $masuk->toDateString(),
                'durasi_bulan' => $durasi,
                'tanggal_berakhir' => $masuk->copy()->addMonths($durasi)->toDateString(),
                'deposit' => $deposit,
                'catatan' => $catatan,
                'status' => $status,
            ]);

            return [$nama => $tenant];
        });

        User::create([
            'name' => 'Pak Joko',
            'email' => 'admin@kosku.id',
            'password' => Hash::make('demo1234'),
            'role' => 'pemilik',
            'aktif' => true,
        ]);

        $tenants->each(function (Tenant $tenant) {
            $user = User::create([
                'name' => $tenant->nama,
                'email' => $tenant->email,
                'password' => Hash::make($tenant->defaultPassword()),
                'role' => 'penghuni',
                'aktif' => $tenant->status === 'Aktif',
            ]);

            $tenant->update(['user_id' => $user->id]);
        });

        $activeTenants = $tenants->filter(fn (Tenant $tenant) => $tenant->status === 'Aktif' && $tenant->room_id);
        $billIndex = 0;

        foreach ($activeTenants as $tenant) {
            $room = Room::find($tenant->room_id);

            for ($i = 2; $i >= 0; $i--) {
                $period = $now->copy()->startOfMonth()->subMonths($i);
                $listrik = 80000 + ($billIndex * 7000);
                $air = 30000 + ($billIndex * 2500);
                $total = (float) $room->harga + $listrik + $air;

                $status = 'Lunas';
                if ($i === 0) {
                    $status = $billIndex % 2 === 0 ? 'Belum Lunas' : 'Lunas';
                } elseif ($i === 1 && $billIndex === 0) {
                    $status = 'Terlambat';
                }

                $paid = $status === 'Lunas';

                Bill::create([
                    'tenant_id' => $tenant->id,
                    'bulan' => $period->month,
                    'tahun' => $period->year,
                    'sewa' => $room->harga,
                    'listrik' => $listrik,
                    'air' => $air,
                    'tambahan' => 0,
                    'ket_tambahan' => '',
                    'total' => $total,
                    'jatuh_tempo' => $period->copy()->day(10)->toDateString(),
                    'status' => $status,
                    'tanggal_bayar' => $paid ? $period->copy()->day(5 + ($billIndex % 6))->toDateString() : null,
                    'metode' => $paid ? 'Transfer Bank' : null,
                ]);

                $billIndex++;
            }

            $billIndex++;
        }

        $complaintRows = [
            ['Budi Santoso', 'Fasilitas Rusak', 'Sedang', 'AC tidak dingin', 'AC kamar K201 sudah seminggu tidak dingin meski sudah dibersihkan.', 'Selesai', 21, 'Sudah diperbaiki teknisi, freon ditambah.', ['Laporan masuk', 'Diproses oleh pemilik', 'Selesai diperbaiki']],
            ['Siti Rahayu', 'Listrik & Air', 'Sedang', 'WiFi sangat lemot', 'Koneksi WiFi di lantai 2 sangat lambat sejak 3 hari lalu.', 'Diproses', 6, 'Sedang dicek oleh provider.', ['Laporan masuk', 'Tiket dibuat ke ISP']],
            ['Ahmad Fauzi', 'Fasilitas Rusak', 'Tinggi', 'Kran kamar mandi bocor', 'Kran wastafel bocor cukup deras sejak pagi, air menggenang.', 'Baru', 0, '', ['Laporan masuk']],
            ['Dewi Lestari', 'Listrik & Air', 'Rendah', 'Lampu kamar mati', 'Lampu utama kamar mati, perlu diganti.', 'Selesai', 30, 'Lampu LED diganti baru.', ['Laporan masuk', 'Selesai diganti']],
            ['Nurul Hidayah', 'Fasilitas Rusak', 'Sedang', 'Pintu kamar macet', 'Engsel pintu kendor, sulit dibuka tutup.', 'Diproses', 4, 'Tukang kayu dijadwalkan besok.', ['Laporan masuk', 'Diproses']],
        ];

        foreach ($complaintRows as [$nama, $kategori, $prioritas, $judul, $deskripsi, $status, $daysAgo, $catatan, $timeline]) {
            $tenant = $tenants[$nama];
            $complaint = Complaint::create([
                'tenant_id' => $tenant->id,
                'kategori' => $kategori,
                'prioritas' => $prioritas,
                'judul' => $judul,
                'deskripsi' => $deskripsi,
                'status' => $status,
                'tanggal_lapor' => $now->copy()->subDays($daysAgo)->toDateString(),
                'tanggal_selesai' => $status === 'Selesai' ? $now->copy()->subDays(max($daysAgo - 2, 0))->toDateString() : null,
                'catatan' => $catatan,
            ]);

            foreach ($timeline as $index => $label) {
                $complaint->events()->create([
                    'tanggal' => $now->copy()->subDays(max($daysAgo - $index, 0))->toDateString(),
                    'label' => $label,
                ]);
            }
        }

        $settings = [
            'nama_kos' => 'Kos KosKu Sentral',
            'no_hp' => '081234567890',
            'alamat' => 'Jl. Merdeka No. 100, Bandung, Jawa Barat',
            'email' => 'info@kosku.id',
            'deskripsi' => 'Kos modern dan nyaman di pusat kota.',
            'tgl_generate' => '1',
            'jatuh_tempo' => '10',
            'denda_persen' => '1',
            'denda_flat' => '10000',
            'pengingat_otomatis' => '1',
            'notifikasi_wa' => '1',
        ];

        foreach ($settings as $key => $value) {
            Setting::put($key, $value);
        }
    }
}
