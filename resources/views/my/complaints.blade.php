@extends('layouts.app')

@section('title', 'Pengaduan Saya — KosKu')

@section('content')
<div x-data="{
    open: false,
    detailOpen: false,
    detailLoading: false,
    detail: null,
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
    }
}">
    <div class="space-y-6">
        <x-page-header title="Pengaduan Saya" subtitle="Daftar dan status pengaduan Anda">
            <button type="button" class="btn btn-primary" @click="open = true">
                <i data-lucide="plus" class="h-4 w-4"></i> Buat Pengaduan
            </button>
        </x-page-header>

        @if ($complaints->isEmpty())
            <div class="card">
                <x-empty-state icon="megaphone" title="Belum ada pengaduan" description="Klik 'Buat Pengaduan' untuk melaporkan masalah.">
                    <button type="button" class="btn btn-primary btn-sm" @click="open = true">Buat Pengaduan</button>
                </x-empty-state>
            </div>
        @else
            <div class="grid gap-3">
                @foreach ($complaints as $complaint)
                    <div class="card transition hover:shadow-md">
                        <div class="flex flex-wrap items-start justify-between gap-4 p-5">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold">{{ $complaint->judul }}</span>
                                    <x-priority-pill :priority="$complaint->prioritas" />
                                </div>
                                <div class="mt-1 text-xs text-muted-foreground">{{ $complaint->kategori }} • {{ $complaint->tanggal_lapor->locale('id')->translatedFormat('j F Y') }}</div>
                                <p class="mt-2 line-clamp-2 text-sm text-foreground/80">{{ $complaint->deskripsi }}</p>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <x-complaint-status :status="$complaint->status" />
                                <button type="button" class="btn btn-ghost btn-sm" @click="showDetail({{ $complaint->id }})">
                                    <i data-lucide="eye" class="h-3.5 w-3.5"></i> Detail
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div x-show="open" x-cloak class="modal-backdrop" @click="open = false"></div>
    <div x-show="open" x-cloak x-transition class="modal-panel max-w-xl">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold">Buat Pengaduan</h2>
                <p class="mt-1 text-sm text-muted-foreground">Lengkapi detail pengaduan dengan jelas.</p>
            </div>
            <button type="button" class="btn btn-ghost btn-icon -mr-2 -mt-2" @click="open = false"><i data-lucide="x" class="h-4 w-4"></i></button>
        </div>
        <form action="{{ route('my.complaints.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Kategori</label>
                    <select name="kategori" class="select">
                        @foreach (\App\Models\Complaint::CATEGORIES as $kategori)
                            <option value="{{ $kategori }}">{{ $kategori }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Prioritas</label>
                    <select name="prioritas" class="select">
                        <option value="Tinggi">Tinggi</option>
                        <option value="Sedang" selected>Sedang</option>
                        <option value="Rendah">Rendah</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="label">Judul Pengaduan</label>
                <input type="text" name="judul" class="input" placeholder="Singkat dan jelas" required>
            </div>
            <div>
                <label class="label">Deskripsi Lengkap</label>
                <textarea name="deskripsi" rows="4" class="textarea" placeholder="Jelaskan masalah secara detail..." required></textarea>
            </div>
            <div>
                <label class="label">Foto Bukti</label>
                <input type="file" accept="image/*" multiple class="input py-1.5">
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
