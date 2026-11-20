@extends('layouts.app')

@section('title', 'Dashboard — KosKu')

@section('content')
<div class="space-y-6">
    <x-page-header :title="'Halo, '.explode(' ', $tenant->nama)[0].' 👋'" subtitle="Ringkasan kos dan tagihan Anda" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-1">
            <div class="p-6">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary text-primary-foreground">
                        <i data-lucide="home" class="h-6 w-6"></i>
                    </div>
                    <div>
                        <div class="text-xs uppercase text-muted-foreground">Kamar</div>
                        <div class="text-2xl font-bold">{{ $room?->nomor ?? '-' }}</div>
                        <div class="text-xs text-muted-foreground">{{ $room?->tipe }} • Lantai {{ $room?->lantai }}</div>
                    </div>
                </div>
                <div class="mt-5 space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-muted-foreground">Masuk</span><span class="font-medium">{{ $tenant->tanggal_masuk->locale('id')->translatedFormat('j F Y') }}</span></div>
                    <div class="flex justify-between"><span class="text-muted-foreground">Berakhir</span><span class="font-medium">{{ $tenant->tanggal_berakhir->locale('id')->translatedFormat('j F Y') }}</span></div>
                    <div class="flex justify-between"><span class="text-muted-foreground">Sisa Kontrak</span><span class="font-semibold text-primary">{{ $sisaHari }} hari</span></div>
                </div>
            </div>
        </div>

        <div class="card border-0 bg-gradient-to-br from-primary to-[oklch(0.27_0.05_254)] text-primary-foreground lg:col-span-2">
            <div class="p-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="text-xs uppercase tracking-wider opacity-80">Tagihan Bulan Ini</div>
                        <div class="mt-1 text-3xl font-bold">{{ $currentBill ? 'Rp '.number_format((float) $currentBill->total, 0, ',', '.') : '-' }}</div>
                        @if ($currentBill)
                            <div class="mt-2 text-sm opacity-90">Jatuh tempo: {{ $currentBill->jatuh_tempo->locale('id')->translatedFormat('j F Y') }}</div>
                        @endif
                    </div>
                    @if ($currentBill)
                        <x-bill-status :status="$currentBill->status" />
                    @endif
                </div>
                <div class="mt-6 grid grid-cols-3 gap-3">
                    <a href="{{ route('my.bills.index') }}" class="btn border-0 bg-white/15 text-primary-foreground hover:bg-white/25">
                        <i data-lucide="credit-card" class="h-4 w-4"></i> Bayar
                    </a>
                    <a href="{{ route('my.complaints.index') }}" class="btn border-0 bg-white/15 text-primary-foreground hover:bg-white/25">
                        <i data-lucide="megaphone" class="h-4 w-4"></i> Pengaduan
                    </a>
                    <a href="{{ route('my.profile.index') }}" class="btn border-0 bg-accent text-accent-foreground hover:bg-accent/90">
                        <i data-lucide="file-text" class="h-4 w-4"></i> Kontrak
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="flex items-center gap-2 border-b border-border px-6 py-4">
            <i data-lucide="calendar-days" class="h-4 w-4"></i>
            <h2 class="text-base font-semibold">Riwayat Pembayaran</h2>
        </div>
        <div class="overflow-x-auto p-2">
            <table class="table-base">
                <thead>
                    <tr><th>Bulan</th><th>Total</th><th>Status</th><th>Tanggal Bayar</th></tr>
                </thead>
                <tbody>
                    @forelse ($myBills->take(6) as $bill)
                        <tr>
                            <td class="font-medium">{{ ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'][$bill->bulan - 1] }} {{ $bill->tahun }}</td>
                            <td>Rp {{ number_format((float) $bill->total, 0, ',', '.') }}</td>
                            <td><x-bill-status :status="$bill->status" /></td>
                            <td>{{ $bill->tanggal_bayar ? $bill->tanggal_bayar->locale('id')->translatedFormat('j F Y') : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-sm text-muted-foreground">Belum ada tagihan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="border-b border-border px-6 py-4">
            <h2 class="text-base font-semibold">Pengaduan Aktif</h2>
        </div>
        <div class="p-6">
            @if ($myComplaints->isEmpty())
                <div class="py-8 text-center text-sm text-muted-foreground">Belum ada pengaduan.</div>
            @else
                <div class="space-y-3">
                    @foreach ($myComplaints as $complaint)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border p-3">
                            <div>
                                <div class="font-medium">{{ $complaint->judul }}</div>
                                <div class="text-xs text-muted-foreground">{{ $complaint->kategori }} • {{ $complaint->tanggal_lapor->locale('id')->translatedFormat('j F Y') }}</div>
                            </div>
                            <x-complaint-status :status="$complaint->status" />
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
