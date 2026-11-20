@extends('layouts.app')

@section('title', 'Manajemen Penghuni — KosKu')

@section('content')
@php
    $occupiedRoomIds = $tenants->where('status', 'Aktif')->pluck('room_id')->filter()->all();
    $roomsJs = $rooms->map(fn ($room) => [
        'id' => $room->id,
        'nomor' => $room->nomor,
        'tipe' => $room->tipe,
        'status' => $room->status,
        'available' => $room->status === 'Tersedia' && ! in_array($room->id, $occupiedRoomIds, true),
    ])->values();
@endphp

<div x-data="{
    open: false,
    editing: false,
    roomsData: @js($roomsJs),
    form: { id: null, nama: '', nik: '', hp: '', email: '', password: '', alamat: '', room_id: '', tanggal_masuk: '{{ now()->toDateString() }}', durasi_bulan: 12, deposit: 0, catatan: '', status: 'Aktif' },
    search: '',
    statusF: 'all',
    openCreate() {
        this.editing = false;
        this.form = { id: null, nama: '', nik: '', hp: '', email: '', password: '', alamat: '', room_id: '', tanggal_masuk: '{{ now()->toDateString() }}', durasi_bulan: 12, deposit: 0, catatan: '', status: 'Aktif' };
        this.open = true;
    },
    openEdit(tenant) {
        this.editing = true;
        this.form = { ...tenant, password: '', room_id: tenant.room_id ? String(tenant.room_id) : '' };
        this.open = true;
    },
    matches(tenant) {
        const q = this.search.toLowerCase();
        if (q && !tenant.nama.toLowerCase().includes(q) && !(tenant.room_nomor || '').toLowerCase().includes(q)) return false;
        if (this.statusF !== 'all' && tenant.status !== this.statusF) return false;
        return true;
    },
    tanggalBerakhir() {
        if (!this.form.tanggal_masuk) return '-';
        const date = new Date(this.form.tanggal_masuk + 'T00:00:00');
        date.setMonth(date.getMonth() + Number(this.form.durasi_bulan || 0));
        return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
    }
}">
    <div class="space-y-6">
        <x-page-header title="Manajemen Penghuni" :subtitle="$tenants->count().' penghuni terdaftar'">
            <button type="button" class="btn btn-primary" @click="openCreate()">
                <i data-lucide="plus" class="h-4 w-4"></i> Tambah Penghuni
            </button>
        </x-page-header>

        <div class="card">
            <div class="flex flex-col gap-3 p-4 lg:flex-row">
                <div class="relative flex-1">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                    <input type="text" x-model="search" class="input pl-9" placeholder="Cari nama atau kamar...">
                </div>
                <select x-model="statusF" class="select lg:w-[180px]">
                    <option value="all">Semua Status</option>
                    <option value="Aktif">Aktif</option>
                    <option value="Tidak Aktif">Tidak Aktif</option>
                </select>
            </div>
        </div>

        <div class="card">
            <div class="p-0">
                @if ($tenants->isEmpty())
                    <x-empty-state icon="users" title="Belum ada penghuni" description="Tambahkan penghuni untuk mulai mengelola kos." />
                @else
                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead>
                                <tr>
                                    <th>Penghuni</th>
                                    <th>NIK</th>
                                    <th>HP</th>
                                    <th>Kamar</th>
                                    <th>Masuk</th>
                                    <th>Berakhir</th>
                                    <th>Status</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tenants as $tenant)
                                    @php
                                        $initials = collect(explode(' ', $tenant->nama))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('');
                                        $tData = [
                                            'id' => $tenant->id,
                                            'nama' => $tenant->nama,
                                            'nik' => $tenant->nik,
                                            'hp' => $tenant->hp,
                                            'email' => $tenant->email,
                                            'alamat' => $tenant->alamat,
                                            'room_id' => $tenant->room_id,
                                            'tanggal_masuk' => $tenant->tanggal_masuk->toDateString(),
                                            'durasi_bulan' => $tenant->durasi_bulan,
                                            'deposit' => (float) $tenant->deposit,
                                            'catatan' => $tenant->catatan,
                                            'status' => $tenant->status,
                                        ];
                                    @endphp
                                    <tr x-show="matches(@js(['nama' => $tenant->nama, 'room_nomor' => $tenant->room?->nomor, 'status' => $tenant->status]))">
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground">{{ strtoupper($initials) }}</div>
                                                <div>
                                                    <div class="font-medium">{{ $tenant->nama }}</div>
                                                    <div class="text-xs text-muted-foreground">{{ $tenant->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="font-mono text-xs">{{ $tenant->nik }}</td>
                                        <td>{{ $tenant->hp }}</td>
                                        <td><span class="font-medium">{{ $tenant->room?->nomor ?? '—' }}</span></td>
                                        <td class="text-sm">{{ $tenant->tanggal_masuk->locale('id')->translatedFormat('j M Y') }}</td>
                                        <td class="text-sm">{{ $tenant->tanggal_berakhir->locale('id')->translatedFormat('j M Y') }}</td>
                                        <td><x-tenant-status :status="$tenant->status" /></td>
                                        <td class="text-right">
                                            <button type="button" class="btn btn-ghost btn-icon" @click="openEdit(@js($tData))"><i data-lucide="pencil" class="h-3.5 w-3.5"></i></button>
                                            <button type="button" class="btn btn-ghost btn-icon text-destructive hover:text-destructive"
                                                @click="$dispatch('confirm-delete', { action: '{{ route('tenants.destroy', $tenant) }}', title: 'Hapus penghuni?', message: 'Data penghuni akan dihapus permanen.' })">
                                                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div x-show="open" x-cloak class="modal-backdrop" @click="open = false"></div>
    <div x-show="open" x-cloak x-transition class="modal-panel max-w-2xl">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold" x-text="editing ? 'Edit Penghuni' : 'Tambah Penghuni'"></h2>
                <p class="mt-1 text-sm text-muted-foreground">Lengkapi data penghuni dengan benar.</p>
            </div>
            <button type="button" class="btn btn-ghost btn-icon -mr-2 -mt-2" @click="open = false"><i data-lucide="x" class="h-4 w-4"></i></button>
        </div>

        <form method="POST" :action="editing ? '{{ url('/penghuni') }}/' + form.id : '{{ route('tenants.store') }}'" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" :value="editing ? 'PATCH' : 'POST'">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="label">Nama Lengkap</label>
                    <input type="text" name="nama" x-model="form.nama" class="input" required>
                </div>
                <div>
                    <label class="label">NIK / No. KTP</label>
                    <input type="text" name="nik" x-model="form.nik" class="input" required>
                </div>
                <div>
                    <label class="label">No. HP / WhatsApp</label>
                    <input type="text" name="hp" x-model="form.hp" class="input" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Email</label>
                    <input type="email" name="email" x-model="form.email" class="input" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Password Akun</label>
                    <input type="password" name="password" x-model="form.password" class="input" minlength="8" placeholder="Kosongkan untuk default: nama penghuni + kode kamar">
                    <p class="mt-1 text-xs text-muted-foreground" x-text="editing ? 'Kosongkan jika password tidak diubah.' : 'Password default dibuat dari nama penghuni + kode kamar.'"></p>
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Alamat Asal</label>
                    <textarea name="alamat" x-model="form.alamat" rows="2" class="textarea" required></textarea>
                </div>
                <div>
                    <label class="label">Kamar</label>
                    <select name="room_id" x-model="form.room_id" class="select">
                        <option value="">— Belum ada kamar —</option>
                        <template x-for="room in roomsData" :key="room.id">
                            <option :value="String(room.id)" :disabled="!room.available && String(room.id) !== String(form.room_id)"
                                x-text="room.nomor + ' (' + room.tipe + ')' + ((room.available || String(room.id) === String(form.room_id)) ? '' : ' — terisi')"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="label">Status</label>
                    <select name="status" x-model="form.status" class="select">
                        <option value="Aktif">Aktif</option>
                        <option value="Tidak Aktif">Tidak Aktif</option>
                    </select>
                </div>
                <div>
                    <label class="label">Tanggal Masuk</label>
                    <input type="date" name="tanggal_masuk" x-model="form.tanggal_masuk" class="input" required>
                </div>
                <div>
                    <label class="label">Durasi Kontrak</label>
                    <select name="durasi_bulan" x-model="form.durasi_bulan" class="select">
                        <option value="1">1 bulan</option>
                        <option value="3">3 bulan</option>
                        <option value="6">6 bulan</option>
                        <option value="12">12 bulan</option>
                        <option value="24">24 bulan</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Tanggal Berakhir Kontrak</label>
                    <input type="text" :value="tanggalBerakhir()" class="input bg-muted" readonly>
                </div>
                <div>
                    <label class="label">Foto KTP</label>
                    <input type="file" accept="image/*" class="input py-1.5">
                </div>
                <div>
                    <label class="label">Foto Penghuni</label>
                    <input type="file" accept="image/*" class="input py-1.5">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Uang Deposit (Rp)</label>
                    <input type="number" name="deposit" x-model="form.deposit" class="input" min="0" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Catatan Tambahan</label>
                    <textarea name="catatan" x-model="form.catatan" rows="2" class="textarea"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn btn-outline" @click="open = false">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
