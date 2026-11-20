@extends('layouts.app')

@section('title', 'Manajemen Pengaduan — KosKu')

@section('content')
@php
    $tenantsJs = $tenants->map(fn ($tenant) => [
        'id' => $tenant->id,
        'nama' => $tenant->nama,
        'room_nomor' => $tenant->room?->nomor,
    ])->values();
@endphp

<div x-data="{
    open: false,
    editing: false,
    detailOpen: false,
    detailLoading: false,
    detail: null,
    tenantsData: @js($tenantsJs),
    form: { id: null, tenant_id: '', kategori: 'Fasilitas Rusak', prioritas: 'Sedang', judul: '', deskripsi: '', status: 'Baru', target_selesai: '', catatan: '' },
    search: '',
    statusF: 'all',
    prioF: 'all',
    openCreate() {
        this.editing = false;
        this.form = { id: null, tenant_id: '', kategori: 'Fasilitas Rusak', prioritas: 'Sedang', judul: '', deskripsi: '', status: 'Baru', target_selesai: '', catatan: '' };
        this.open = true;
    },
    openEdit(complaint) {
        this.editing = true;
        this.form = { ...complaint, tenant_id: String(complaint.tenant_id) };
        this.open = true;
    },
    async showDetail(id) {
        this.detailOpen = true;
        this.detailLoading = true;
        this.detail = null;
        try {
            const response = await fetch('{{ url('/pengaduan') }}/' + id, { headers: { 'Accept': 'application/json' } });
            this.detail = await response.json();
        } finally {
            this.detailLoading = false;
        }
    },
    matches(complaint) {
        const q = this.search.toLowerCase();
        if (q && !(complaint.tenant_nama || '').toLowerCase().includes(q) && !(complaint.room_nomor || '').toLowerCase().includes(q)) return false;
        if (this.statusF !== 'all' && complaint.status !== this.statusF) return false;
        if (this.prioF !== 'all' && complaint.prioritas !== this.prioF) return false;
        return true;
    }
}">
    <div class="space-y-6">
        <x-page-header title="Manajemen Pengaduan" :subtitle="$complaints->count().' pengaduan tercatat'">
            <button type="button" class="btn btn-primary" @click="openCreate()">
                <i data-lucide="plus" class="h-4 w-4"></i> Buat Pengaduan
            </button>
        </x-page-header>

        <div class="card">
            <div class="flex flex-col gap-3 p-4 lg:flex-row">
                <div class="relative flex-1">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                    <input type="text" x-model="search" class="input pl-9" placeholder="Cari penghuni atau kamar...">
                </div>
                <select x-model="statusF" class="select lg:w-[150px]">
                    <option value="all">Semua Status</option>
                    <option value="Baru">Baru</option>
                    <option value="Diproses">Diproses</option>
                    <option value="Selesai">Selesai</option>
                    <option value="Ditolak">Ditolak</option>
                </select>
                <select x-model="prioF" class="select lg:w-[150px]">
                    <option value="all">Semua Prioritas</option>
                    <option value="Tinggi">Tinggi</option>
                    <option value="Sedang">Sedang</option>
                    <option value="Rendah">Rendah</option>
                </select>
            </div>
        </div>

        <div class="card">
            <div class="p-0">
                @if ($complaints->isEmpty())
                    <x-empty-state icon="wrench" title="Tidak ada pengaduan" description="Pengaduan dari penghuni akan tampil di sini." />
                @else
                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead>
                                <tr>
                                    <th>Penghuni</th>
                                    <th>Kamar</th>
                                    <th>Kategori</th>
                                    <th>Judul</th>
                                    <th>Prioritas</th>
                                    <th>Status</th>
                                    <th>Tgl Lapor</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($complaints as $complaint)
                                    @php
                                        $cData = [
                                            'id' => $complaint->id,
                                            'tenant_id' => $complaint->tenant_id,
                                            'kategori' => $complaint->kategori,
                                            'prioritas' => $complaint->prioritas,
                                            'judul' => $complaint->judul,
                                            'deskripsi' => $complaint->deskripsi,
                                            'status' => $complaint->status,
                                            'target_selesai' => $complaint->target_selesai?->toDateString() ?? '',
                                            'catatan' => $complaint->catatan,
                                        ];
                                    @endphp
                                    <tr x-show="matches(@js(['tenant_nama' => $complaint->tenant?->nama, 'room_nomor' => $complaint->tenant?->room?->nomor, 'status' => $complaint->status, 'prioritas' => $complaint->prioritas]))">
                                        <td class="font-medium">{{ $complaint->tenant?->nama ?? '-' }}</td>
                                        <td>{{ $complaint->tenant?->room?->nomor ?? '-' }}</td>
                                        <td class="text-xs">{{ $complaint->kategori }}</td>
                                        <td class="max-w-[200px] truncate">{{ $complaint->judul }}</td>
                                        <td><x-priority-pill :priority="$complaint->prioritas" /></td>
                                        <td><x-complaint-status :status="$complaint->status" /></td>
                                        <td class="text-sm">{{ $complaint->tanggal_lapor->locale('id')->translatedFormat('j M Y') }}</td>
                                        <td class="text-right">
                                            <button type="button" class="btn btn-ghost btn-icon" @click="showDetail({{ $complaint->id }})"><i data-lucide="eye" class="h-3.5 w-3.5"></i></button>
                                            <button type="button" class="btn btn-ghost btn-icon" @click="openEdit(@js($cData))"><i data-lucide="pencil" class="h-3.5 w-3.5"></i></button>
                                            <button type="button" class="btn btn-ghost btn-icon text-destructive hover:text-destructive"
                                                @click="$dispatch('confirm-delete', { action: '{{ route('complaints.destroy', $complaint) }}', title: 'Hapus pengaduan?', message: 'Pengaduan akan dihapus permanen.' })">
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
    <div x-show="open" x-cloak x-transition class="modal-panel max-w-xl">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold" x-text="editing ? 'Edit Pengaduan' : 'Buat Pengaduan'"></h2>
                <p class="mt-1 text-sm text-muted-foreground">Lengkapi detail pengaduan dengan jelas.</p>
            </div>
            <button type="button" class="btn btn-ghost btn-icon -mr-2 -mt-2" @click="open = false"><i data-lucide="x" class="h-4 w-4"></i></button>
        </div>

        <form method="POST" :action="editing ? '{{ url('/pengaduan') }}/' + form.id : '{{ route('complaints.store') }}'" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" :value="editing ? 'PATCH' : 'POST'">
            <div>
                <label class="label">Penghuni</label>
                <select name="tenant_id" x-model="form.tenant_id" class="select" required>
                    <option value="">Pilih penghuni...</option>
                    <template x-for="tenant in tenantsData" :key="tenant.id">
                        <option :value="String(tenant.id)" x-text="tenant.nama + (tenant.room_nomor ? ' (' + tenant.room_nomor + ')' : '')"></option>
                    </template>
                </select>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Kategori</label>
                    <select name="kategori" x-model="form.kategori" class="select">
                        @foreach (\App\Models\Complaint::CATEGORIES as $kategori)
                            <option value="{{ $kategori }}">{{ $kategori }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Prioritas</label>
                    <select name="prioritas" x-model="form.prioritas" class="select">
                        <option value="Tinggi">Tinggi</option>
                        <option value="Sedang">Sedang</option>
                        <option value="Rendah">Rendah</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="label">Judul Pengaduan</label>
                <input type="text" name="judul" x-model="form.judul" class="input" placeholder="Singkat dan jelas" required>
            </div>
            <div>
                <label class="label">Deskripsi Lengkap</label>
                <textarea name="deskripsi" x-model="form.deskripsi" rows="4" class="textarea" placeholder="Jelaskan masalah secara detail..." required></textarea>
            </div>
            <div>
                <label class="label">Foto Bukti</label>
                <input type="file" accept="image/*" multiple class="input py-1.5">
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Status</label>
                    <select name="status" x-model="form.status" class="select">
                        <option value="Baru">Baru</option>
                        <option value="Diproses">Diproses</option>
                        <option value="Selesai">Selesai</option>
                        <option value="Ditolak">Ditolak</option>
                    </select>
                </div>
                <div>
                    <label class="label">Target Selesai</label>
                    <input type="date" name="target_selesai" x-model="form.target_selesai" class="input">
                </div>
            </div>
            <div>
                <label class="label">Catatan / Tindakan</label>
                <textarea name="catatan" x-model="form.catatan" rows="2" class="textarea"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn btn-outline" @click="open = false">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>

    <div x-show="detailOpen" x-cloak class="modal-backdrop" @click="detailOpen = false"></div>
    <div x-show="detailOpen" x-cloak x-transition class="modal-panel max-w-xl">
        <div class="mb-4 flex items-start justify-between gap-4">
            <h2 class="text-lg font-semibold" x-text="detail ? detail.judul : 'Detail Pengaduan'"></h2>
            <button type="button" class="btn btn-ghost btn-icon -mr-2 -mt-2" @click="detailOpen = false"><i data-lucide="x" class="h-4 w-4"></i></button>
        </div>
        <div x-show="detailLoading" class="py-10 text-center text-sm text-muted-foreground">Memuat detail...</div>
        <div x-show="!detailLoading && detail" class="space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <template x-if="detail">
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium"
                          :class="detail.status === 'Selesai' ? 'border-success/30 bg-success/15 text-success' : (detail.status === 'Diproses' ? 'border-warning/40 bg-warning/15 text-warning-foreground' : (detail.status === 'Ditolak' ? 'border-destructive/30 bg-destructive/15 text-destructive' : 'border-info/30 bg-info/15 text-info'))"
                          x-text="detail.status"></span>
                </template>
                <template x-if="detail">
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium"
                          :class="detail.prioritas === 'Tinggi' ? 'border-destructive/30 bg-destructive/15 text-destructive' : (detail.prioritas === 'Sedang' ? 'border-warning/40 bg-warning/15 text-warning-foreground' : 'border-success/30 bg-success/15 text-success')"
                          x-text="detail.prioritas"></span>
                </template>
                <span class="rounded-full bg-muted px-2.5 py-0.5 text-xs" x-text="detail?.kategori"></span>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div><div class="text-xs text-muted-foreground">Penghuni</div><div class="font-medium" x-text="detail?.penghuni || '-'"></div></div>
                <div><div class="text-xs text-muted-foreground">Kamar</div><div class="font-medium" x-text="detail?.kamar || '-'"></div></div>
                <div><div class="text-xs text-muted-foreground">Tanggal Lapor</div><div class="font-medium" x-text="detail?.tanggal_lapor || '-'"></div></div>
                <div><div class="text-xs text-muted-foreground">Tanggal Selesai</div><div class="font-medium" x-text="detail?.tanggal_selesai || '-'"></div></div>
            </div>
            <div>
                <div class="mb-1 text-xs text-muted-foreground">Deskripsi</div>
                <div class="rounded-lg bg-muted p-3 text-sm" x-text="detail?.deskripsi"></div>
            </div>
            <template x-if="detail && detail.catatan">
                <div>
                    <div class="mb-1 text-xs text-muted-foreground">Catatan Pengelola</div>
                    <div class="rounded-lg border border-accent/30 bg-accent/5 p-3 text-sm" x-text="detail.catatan"></div>
                </div>
            </template>
            <div>
                <div class="mb-2 text-xs text-muted-foreground">Timeline</div>
                <div class="space-y-2">
                    <template x-for="(event, index) in (detail?.timeline || [])" :key="index">
                        <div class="flex items-start gap-2">
                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full" :class="index === (detail.timeline.length - 1) && detail.status === 'Selesai' ? 'bg-success' : 'bg-muted-foreground'"></span>
                            <div class="flex-1">
                                <div class="text-sm" x-text="event.label"></div>
                                <div class="text-xs text-muted-foreground" x-text="event.tanggal"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
