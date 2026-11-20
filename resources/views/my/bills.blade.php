@extends('layouts.app')

@section('title', 'Tagihan Saya — KosKu')

@section('content')
@php
    $bulanPanjang = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $bulanSingkat = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
@endphp

<div class="space-y-6">
    <x-page-header title="Tagihan Saya" subtitle="Detail dan riwayat pembayaran kos Anda" />

    @if ($current)
        <div class="card border-2 border-primary/20">
            <div class="p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="text-xs uppercase text-muted-foreground">Tagihan Bulan Ini</div>
                        <div class="mt-1 text-2xl font-bold">{{ $bulanPanjang[$current->bulan - 1] }} {{ $current->tahun }}</div>
                        <div class="mt-1 text-sm text-muted-foreground">Jatuh tempo: {{ $current->jatuh_tempo->locale('id')->translatedFormat('j F Y') }}</div>
                    </div>
                    <x-bill-status :status="$current->status" />
                </div>
                <div class="mt-5 grid gap-3 rounded-lg bg-muted/50 p-4 text-sm sm:grid-cols-2">
                    <div class="flex justify-between"><span class="text-muted-foreground">Sewa Kamar</span><span class="font-medium">Rp {{ number_format((float) $current->sewa, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span class="text-muted-foreground">Listrik</span><span class="font-medium">Rp {{ number_format((float) $current->listrik, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span class="text-muted-foreground">Air</span><span class="font-medium">Rp {{ number_format((float) $current->air, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span class="text-muted-foreground">Tambahan</span><span class="font-medium">Rp {{ number_format((float) $current->tambahan, 0, ',', '.') }}</span></div>
                </div>
                <div class="mt-4 flex items-center justify-between rounded-lg bg-primary p-4 text-primary-foreground">
                    <span class="text-sm uppercase opacity-80">Total</span>
                    <span class="text-2xl font-bold">Rp {{ number_format((float) $current->total, 0, ',', '.') }}</span>
                </div>

                @if ($current->bukti_bayar)
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-info/30 bg-info/10 p-3 text-sm">
                        <span class="text-info">Bukti bayar sudah diunggah, menunggu verifikasi pemilik.</span>
                        <a href="{{ \Illuminate\Support\Facades\Storage::url($current->bukti_bayar) }}" target="_blank" class="font-medium text-primary underline underline-offset-2">Lihat bukti</a>
                    </div>
                @endif

                @if ($current->status !== 'Lunas')
                    <form action="{{ route('my.bills.proof', $current) }}" method="POST" enctype="multipart/form-data" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-end">
                        @csrf
                        <div class="w-full sm:max-w-xs">
                            <label class="label">Upload Bukti Bayar</label>
                            <input type="file" name="bukti_bayar" accept="image/*,application/pdf" class="input py-1.5" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="upload" class="h-4 w-4"></i> Upload Bukti Bayar
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif

    <div class="card">
        <div class="p-0">
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead><tr><th>Periode</th><th>Total</th><th>Status</th><th>Tanggal Bayar</th><th>Metode</th></tr></thead>
                    <tbody>
                        @forelse ($bills as $bill)
                            <tr>
                                <td class="font-medium">{{ $bulanPanjang[$bill->bulan - 1] }} {{ $bill->tahun }}</td>
                                <td>Rp {{ number_format((float) $bill->total, 0, ',', '.') }}</td>
                                <td><x-bill-status :status="$bill->status" /></td>
                                <td>{{ $bill->tanggal_bayar ? $bill->tanggal_bayar->locale('id')->translatedFormat('j F Y') : '-' }}</td>
                                <td>{{ $bill->metode ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-sm text-muted-foreground">Belum ada tagihan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
