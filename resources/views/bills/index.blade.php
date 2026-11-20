@extends('layouts.app')

@section('title', 'Manajemen Tagihan — KosKu')

@section('content')
@php
    $tenantsJs = $tenants->map(fn ($tenant) => [
        'id' => $tenant->id,
        'nama' => $tenant->nama,
        'room_nomor' => $tenant->room?->nomor,
        'room_harga' => (float) ($tenant->room?->harga ?? 0),
    ])->values();
    $billsJs = $bills->map(fn ($bill) => [
        'id' => $bill->id,
        'tenant_id' => $bill->tenant_id,
        'tenant_nama' => $bill->tenant?->nama,
        'room_nomor' => $bill->tenant?->room?->nomor,
        'bulan' => $bill->bulan,
        'tahun' => $bill->tahun,
        'sewa' => (float) $bill->sewa,
        'listrik' => (float) $bill->listrik,
        'air' => (float) $bill->air,
        'tambahan' => (float) $bill->tambahan,
        'ket_tambahan' => $bill->ket_tambahan,
        'total' => (float) $bill->total,
        'jatuh_tempo' => $bill->jatuh_tempo->toDateString(),
        'status' => $bill->status,
        'tanggal_bayar' => $bill->tanggal_bayar?->toDateString() ?? '',
        'metode' => $bill->metode ?? '',
        'bukti_bayar' => $bill->bukti_bayar,
    ])->values();
    $bulanSingkat = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
@endphp

<div x-data="{
    open: false,
    editing: false,
    tenantsData: @js($tenantsJs),
    form: { id: null, tenant_id: '', bulan: {{ now()->month }}, tahun: {{ now()->year }}, sewa: 0, listrik: 0, air: 0, tambahan: 0, ket_tambahan: '', jatuh_tempo: '{{ now()->copy()->day(10)->toDateString() }}', status: 'Belum Lunas', tanggal_bayar: '', metode: '' },
    search: '',
    statusF: 'all',
    bulanF: 'all',
    openCreate() {
        this.editing = false;
        this.form = { id: null, tenant_id: '', bulan: {{ now()->month }}, tahun: {{ now()->year }}, sewa: 0, listrik: 0, air: 0, tambahan: 0, ket_tambahan: '', jatuh_tempo: '{{ now()->copy()->day(10)->toDateString() }}', status: 'Belum Lunas', tanggal_bayar: '', metode: '' };
        this.open = true;
    },
    openEdit(bill) {
        this.editing = true;
        this.form = { ...bill, tenant_id: String(bill.tenant_id) };
        this.open = true;
    },
    onTenantChange() {
        const tenant = this.tenantsData.find(item => String(item.id) === String(this.form.tenant_id));
        if (tenant) this.form.sewa = tenant.room_harga || 0;
    },
    total() {
        return Number(this.form.sewa || 0) + Number(this.form.listrik || 0) + Number(this.form.air || 0) + Number(this.form.tambahan || 0);
    },
    rupiah(value) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(Number(value) || 0));
    },
    matches(bill) {
        const q = this.search.toLowerCase();
        if (q && !(bill.tenant_nama || '').toLowerCase().includes(q)) return false;
        if (this.statusF !== 'all' && bill.status !== this.statusF) return false;
        if (this.bulanF !== 'all' && String(bill.bulan) !== this.bulanF) return false;
        return true;
    }
}">
    <div class="space-y-6">
        <x-page-header title="Manajemen Tagihan" :subtitle="$bills->count().' tagihan terdaftar'">
            <button type="button" class="btn btn-outline"
                @click="$dispatch('confirm-delete', { action: '{{ route('bills.generate') }}', method: 'POST', title: 'Generate Tagihan Bulan Ini?', message: 'Sistem akan membuat tagihan otomatis untuk semua penghuni aktif yang belum memiliki tagihan bulan ini.', confirmLabel: 'Generate', danger: false })">
                <i data-lucide="zap" class="h-4 w-4"></i> Generate Bulanan
            </button>
            <button type="button" class="btn btn-primary" @click="openCreate()">
                <i data-lucide="plus" class="h-4 w-4"></i> Buat Tagihan
            </button>
        </x-page-header>

        <div class="card">
            <div class="flex flex-col gap-3 p-4 lg:flex-row">
                <div class="relative flex-1">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                    <input type="text" x-model="search" class="input pl-9" placeholder="Cari nama penghuni...">
                </div>
                <select x-model="statusF" class="select lg:w-[170px]">
                    <option value="all">Semua Status</option>
                    <option value="Lunas">Lunas</option>
                    <option value="Belum Lunas">Belum Lunas</option>
                    <option value="Terlambat">Terlambat</option>
                </select>
                <select x-model="bulanF" class="select lg:w-[150px]">
                    <option value="all">Semua Bulan</option>
                    @foreach ($bulanSingkat as $index => $bulan)
                        <option value="{{ $index + 1 }}">{{ $bulan }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="card">
            <div class="p-0">
                @if ($bills->isEmpty())
                    <x-empty-state icon="wallet" title="Belum ada tagihan" description="Buat tagihan manual atau generate bulanan." />
                @else
                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead>
                                <tr>
                                    <th>Penghuni</th>
                                    <th>Kamar</th>
                                    <th>Periode</th>
                                    <th>Rincian</th>
                                    <th>Total</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Status</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bills as $bill)
                                    @php
                                        $bData = collect($billsJs)->firstWhere('id', $bill->id);
                                    @endphp
                                    <tr x-show="matches(@js(['tenant_nama' => $bill->tenant?->nama, 'status' => $bill->status, 'bulan' => $bill->bulan]))">
                                        <td class="font-medium">{{ $bill->tenant?->nama ?? '-' }}</td>
                                        <td>{{ $bill->tenant?->room?->nomor ?? '-' }}</td>
                                        <td>{{ $bulanSingkat[$bill->bulan - 1] }} {{ $bill->tahun }}</td>
                                        <td class="text-xs text-muted-foreground">
                                            Sewa Rp {{ number_format((float) $bill->sewa, 0, ',', '.') }}<br>
                                            + L Rp {{ number_format((float) $bill->listrik, 0, ',', '.') }} • A Rp {{ number_format((float) $bill->air, 0, ',', '.') }}
                                            @if ((float) $bill->tambahan > 0)
                                                <br>+ Tambahan Rp {{ number_format((float) $bill->tambahan, 0, ',', '.') }}
                                            @endif
                                        </td>
                                        <td class="font-bold">Rp {{ number_format((float) $bill->total, 0, ',', '.') }}</td>
                                        <td class="text-sm">{{ $bill->jatuh_tempo->locale('id')->translatedFormat('j M Y') }}</td>
                                        <td>
                                            <div class="flex flex-col items-start gap-1">
                                                <x-bill-status :status="$bill->status" />
                                                @if ($bill->bukti_bayar)
                                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($bill->bukti_bayar) }}" target="_blank" class="text-[11px] font-medium text-primary underline underline-offset-2">Lihat bukti</a>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            <button type="button" class="btn btn-ghost btn-icon" @click="openEdit(@js($bData))"><i data-lucide="pencil" class="h-3.5 w-3.5"></i></button>
                                            <button type="button" class="btn btn-ghost btn-icon text-destructive hover:text-destructive"
                                                @click="$dispatch('confirm-delete', { action: '{{ route('bills.destroy', $bill) }}', title: 'Hapus tagihan?', message: 'Tagihan ini akan dihapus permanen.' })">
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
                <h2 class="text-lg font-semibold" x-text="editing ? 'Edit Tagihan' : 'Buat Tagihan Manual'"></h2>
                <p class="mt-1 text-sm text-muted-foreground">Isi rincian tagihan dengan teliti.</p>
            </div>
            <button type="button" class="btn btn-ghost btn-icon -mr-2 -mt-2" @click="open = false"><i data-lucide="x" class="h-4 w-4"></i></button>
        </div>

        <form method="POST" :action="editing ? '{{ url('/tagihan') }}/' + form.id : '{{ route('bills.store') }}'" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" :value="editing ? 'PATCH' : 'POST'">
            <div>
                <label class="label">Pilih Penghuni</label>
                <select name="tenant_id" x-model="form.tenant_id" class="select" @change="onTenantChange()" required>
                    <option value="">Pilih penghuni...</option>
                    <template x-for="tenant in tenantsData" :key="tenant.id">
                        <option :value="String(tenant.id)" x-text="tenant.nama + (tenant.room_nomor ? ' (' + tenant.room_nomor + ')' : '')"></option>
                    </template>
                </select>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Bulan</label>
                    <select name="bulan" x-model="form.bulan" class="select">
                        @foreach (['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $index => $bulan)
                            <option value="{{ $index + 1 }}">{{ $bulan }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Tahun</label>
                    <input type="number" name="tahun" x-model="form.tahun" class="input" min="2020" required>
                </div>
                <div>
                    <label class="label">Harga Sewa (Rp)</label>
                    <input type="number" name="sewa" x-model="form.sewa" class="input" min="0" required>
                </div>
                <div>
                    <label class="label">Tagihan Listrik (Rp)</label>
                    <input type="number" name="listrik" x-model="form.listrik" class="input" min="0" required>
                </div>
                <div>
                    <label class="label">Tagihan Air (Rp)</label>
                    <input type="number" name="air" x-model="form.air" class="input" min="0" required>
                </div>
                <div>
                    <label class="label">Biaya Tambahan (Rp)</label>
                    <input type="number" name="tambahan" x-model="form.tambahan" class="input" min="0" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Keterangan Tambahan</label>
                    <input type="text" name="ket_tambahan" x-model="form.ket_tambahan" class="input" placeholder="Misal: denda, perbaikan, dll">
                </div>
            </div>

            <div class="rounded-lg border-2 border-primary/20 bg-primary/5 p-4">
                <div class="text-xs uppercase text-muted-foreground">Total Tagihan</div>
                <div class="mt-1 text-3xl font-bold text-primary" x-text="rupiah(total())"></div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Tanggal Jatuh Tempo</label>
                    <input type="date" name="jatuh_tempo" x-model="form.jatuh_tempo" class="input" required>
                </div>
                <div>
                    <label class="label">Status</label>
                    <select name="status" x-model="form.status" class="select">
                        <option value="Belum Lunas">Belum Lunas</option>
                        <option value="Lunas">Lunas</option>
                        <option value="Terlambat">Terlambat</option>
                    </select>
                </div>
                <template x-if="form.status === 'Lunas'">
                    <div class="contents">
                        <div>
                            <label class="label">Tanggal Bayar</label>
                            <input type="date" name="tanggal_bayar" x-model="form.tanggal_bayar" class="input">
                        </div>
                        <div>
                            <label class="label">Metode Pembayaran</label>
                            <select name="metode" x-model="form.metode" class="select">
                                <option value="">Pilih metode</option>
                                <option value="Transfer Bank">Transfer Bank</option>
                                <option value="Cash">Cash</option>
                                <option value="QRIS">QRIS</option>
                            </select>
                        </div>
                    </div>
                </template>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn btn-outline" @click="open = false">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
