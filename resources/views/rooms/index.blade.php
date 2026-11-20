@extends('layouts.app')

@section('title', 'Manajemen Kamar — KosKu')

@section('content')
@php
    $roomsJs = $rooms->map(fn ($room) => [
        'id' => $room->id,
        'nomor' => $room->nomor,
        'tipe' => $room->tipe,
        'lantai' => $room->lantai,
        'harga' => (float) $room->harga,
        'luas' => $room->luas,
        'status' => $room->status,
        'fasilitas' => $room->fasilitas,
        'deskripsi' => $room->deskripsi,
    ])->values();
@endphp

<div x-data="{
    open: false,
    editing: false,
    form: { id: null, nomor: '', tipe: 'Standard', lantai: 1, harga: 800000, luas: 9, status: 'Tersedia', fasilitas: [], deskripsi: '' },
    roomsData: @js($roomsJs),
    search: '',
    statusF: 'all',
    tipeF: 'all',
    lantaiF: 'all',
    openCreate() {
        this.editing = false;
        this.form = { id: null, nomor: '', tipe: 'Standard', lantai: 1, harga: 800000, luas: 9, status: 'Tersedia', fasilitas: [], deskripsi: '' };
        this.open = true;
    },
    openEdit(room) {
        this.editing = true;
        this.form = { ...room };
        this.open = true;
    },
    matches(room) {
        const q = this.search.toLowerCase();
        if (q && !room.nomor.toLowerCase().includes(q)) return false;
        if (this.statusF !== 'all' && room.status !== this.statusF) return false;
        if (this.tipeF !== 'all' && room.tipe !== this.tipeF) return false;
        if (this.lantaiF !== 'all' && String(room.lantai) !== this.lantaiF) return false;
        return true;
    },
    toggleFasilitas(item) {
        const index = this.form.fasilitas.indexOf(item);
        if (index >= 0) this.form.fasilitas.splice(index, 1);
        else this.form.fasilitas.push(item);
    }
}">
    <div class="space-y-6">
        <x-page-header title="Manajemen Kamar" :subtitle="$rooms->count().' kamar terdaftar'">
            <button type="button" class="btn btn-primary" @click="openCreate()">
                <i data-lucide="plus" class="h-4 w-4"></i> Tambah Kamar
            </button>
        </x-page-header>

        <div class="card">
            <div class="flex flex-col gap-3 p-4 lg:flex-row lg:items-center">
                <div class="relative flex-1">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                    <input type="text" x-model="search" class="input pl-9" placeholder="Cari nomor kamar...">
                </div>
                <select x-model="statusF" class="select lg:w-[170px]">
                    <option value="all">Semua Status</option>
                    <option value="Tersedia">Tersedia</option>
                    <option value="Terisi">Terisi</option>
                    <option value="Dalam Perawatan">Dalam Perawatan</option>
                </select>
                <select x-model="tipeF" class="select lg:w-[140px]">
                    <option value="all">Semua Tipe</option>
                    <option value="Standard">Standard</option>
                    <option value="Deluxe">Deluxe</option>
                    <option value="VIP">VIP</option>
                </select>
                <select x-model="lantaiF" class="select lg:w-[130px]">
                    <option value="all">Semua Lantai</option>
                    @foreach ($lantai as $floor)
                        <option value="{{ $floor }}">Lantai {{ $floor }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div x-show="!roomsData.some(room => matches(room))" x-cloak class="card">
            <x-empty-state icon="bed" title="Tidak ada kamar" description="Belum ada kamar sesuai filter." />
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($rooms as $room)
                @php
                    $rData = [
                        'id' => $room->id,
                        'nomor' => $room->nomor,
                        'tipe' => $room->tipe,
                        'lantai' => $room->lantai,
                        'harga' => (float) $room->harga,
                        'luas' => $room->luas,
                        'status' => $room->status,
                        'fasilitas' => $room->fasilitas,
                        'deskripsi' => $room->deskripsi,
                    ];
                @endphp
                <div x-show="matches(@js($rData))" class="card overflow-hidden transition hover:shadow-md">
                    <div class="flex aspect-[16/10] items-center justify-center bg-gradient-to-br from-primary/10 to-accent/20">
                        <i data-lucide="bed" class="h-12 w-12 text-primary/50"></i>
                    </div>
                    <div class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="text-lg font-bold">{{ $room->nomor }}</div>
                                <div class="text-xs text-muted-foreground">{{ $room->tipe }} • Lantai {{ $room->lantai }}</div>
                            </div>
                            <x-room-status :status="$room->status" />
                        </div>
                        <div class="text-xl font-bold text-primary">Rp {{ number_format((float) $room->harga, 0, ',', '.') }}<span class="text-xs font-normal text-muted-foreground">/bln</span></div>
                        <div class="flex flex-wrap gap-1">
                            @foreach (array_slice($room->fasilitas ?? [], 0, 3) as $fasilitas)
                                <span class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-[10px] font-medium text-muted-foreground">
                                    @if ($fasilitas === 'AC')
                                        <i data-lucide="snowflake" class="h-2.5 w-2.5"></i>
                                    @elseif ($fasilitas === 'WiFi')
                                        <i data-lucide="wifi" class="h-2.5 w-2.5"></i>
                                    @endif
                                    {{ $fasilitas }}
                                </span>
                            @endforeach
                            @if (count($room->fasilitas ?? []) > 3)
                                <span class="text-[10px] text-muted-foreground">+{{ count($room->fasilitas) - 3 }}</span>
                            @endif
                        </div>
                        <div class="flex gap-2 pt-2">
                            <button type="button" class="btn btn-outline btn-sm flex-1" @click="openEdit(@js($rData))">
                                <i data-lucide="pencil" class="h-3 w-3"></i> Edit
                            </button>
                            <button type="button" class="btn btn-outline btn-sm text-destructive hover:bg-destructive/10 hover:text-destructive"
                                @click="$dispatch('confirm-delete', { action: '{{ route('rooms.destroy', $room) }}', title: 'Hapus kamar?', message: 'Tindakan ini tidak dapat dibatalkan. Data kamar akan dihapus permanen.' })">
                                <i data-lucide="trash-2" class="h-3 w-3"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div x-show="open" x-cloak class="modal-backdrop" @click="open = false"></div>
    <div x-show="open" x-cloak x-transition class="modal-panel max-w-2xl">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold" x-text="editing ? 'Edit Kamar' : 'Tambah Kamar'"></h2>
                <p class="mt-1 text-sm text-muted-foreground">Lengkapi detail kamar di bawah ini.</p>
            </div>
            <button type="button" class="btn btn-ghost btn-icon -mr-2 -mt-2" @click="open = false"><i data-lucide="x" class="h-4 w-4"></i></button>
        </div>

        <form method="POST" :action="editing ? '{{ url('/kamar') }}/' + form.id : '{{ route('rooms.store') }}'" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" :value="editing ? 'PATCH' : 'POST'">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Nomor Kamar</label>
                    <input type="text" name="nomor" x-model="form.nomor" class="input" placeholder="K101" required>
                </div>
                <div>
                    <label class="label">Tipe Kamar</label>
                    <select name="tipe" x-model="form.tipe" class="select">
                        <option value="Standard">Standard</option>
                        <option value="Deluxe">Deluxe</option>
                        <option value="VIP">VIP</option>
                    </select>
                </div>
                <div>
                    <label class="label">Lantai</label>
                    <input type="number" name="lantai" x-model="form.lantai" class="input" min="1" required>
                </div>
                <div>
                    <label class="label">Luas (m²)</label>
                    <input type="number" name="luas" x-model="form.luas" class="input" min="1" required>
                </div>
                <div>
                    <label class="label">Harga Sewa / Bulan (Rp)</label>
                    <input type="number" name="harga" x-model="form.harga" class="input" min="1" required>
                </div>
                <div>
                    <label class="label">Status</label>
                    <select name="status" x-model="form.status" class="select">
                        <option value="Tersedia">Tersedia</option>
                        <option value="Terisi">Terisi</option>
                        <option value="Dalam Perawatan">Dalam Perawatan</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="label">Fasilitas</label>
                <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @foreach (['AC', 'WiFi', 'Kamar Mandi Dalam', 'Lemari', 'Kasur', 'Meja Belajar', 'Water Heater'] as $fasilitas)
                        <label class="flex cursor-pointer items-center gap-2 rounded-md border border-border px-3 py-2 text-sm hover:bg-muted">
                            <input type="checkbox" name="fasilitas[]" value="{{ $fasilitas }}" class="h-4 w-4 rounded border-border text-primary focus:ring-ring"
                                :checked="form.fasilitas.includes('{{ $fasilitas }}')" @change="toggleFasilitas('{{ $fasilitas }}')">
                            <span>{{ $fasilitas }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="label">Foto Kamar</label>
                <input type="file" name="foto" accept="image/*" class="input py-1.5">
            </div>
            <div>
                <label class="label">Deskripsi</label>
                <textarea name="deskripsi" x-model="form.deskripsi" rows="3" class="textarea" placeholder="Deskripsi singkat kamar..."></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn btn-outline" @click="open = false">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
