@extends('layouts.app')

@section('title', 'Dashboard — KosKu')

@section('content')
@php
    $incomeChart = [
        'type' => 'bar',
        'labels' => $incomeData->pluck('name')->all(),
        'datasets' => [[
            'label' => 'Pendapatan',
            'data' => $incomeData->pluck('pendapatan')->all(),
            'backgroundColor' => 'oklch(0.34 0.06 254)',
            'borderRadius' => 8,
            'maxBarThickness' => 44,
        ]],
    ];
    $occupancyChart = [
        'type' => 'doughnut',
        'labels' => collect($occupancyData)->pluck('name')->all(),
        'datasets' => [[
            'data' => collect($occupancyData)->pluck('value')->all(),
            'backgroundColor' => collect($occupancyData)->pluck('color')->all(),
            'borderWidth' => 0,
        ]],
        'legend' => true,
        'money' => false,
    ];
@endphp

<div class="space-y-6">
    <x-page-header title="Dashboard" subtitle="Ringkasan operasional kos — {{ $now->copy()->locale('id')->translatedFormat('j F Y') }}" />

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
        <x-stat-card icon="door-closed" label="Total Kamar" :value="(string) $total" accent="primary" />
        <x-stat-card icon="users" label="Kamar Terisi" :value="(string) $terisi" accent="primary" />
        <x-stat-card icon="door-open" label="Kamar Kosong" :value="(string) $kosong" accent="success" />
        <x-stat-card icon="wallet" label="Belum Lunas" :value="'Rp '.number_format($belumLunasTotal, 0, ',', '.')" accent="danger" />
        <x-stat-card icon="wrench" label="Pengaduan Aktif" :value="(string) $pengaduanAktif" accent="accent" />
        <x-stat-card icon="trending-up" label="Pendapatan Bln Ini" :value="'Rp '.number_format($pendapatanBulanIni, 0, ',', '.')" accent="primary" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="border-b border-border px-6 py-4">
                <h2 class="text-base font-semibold">Pendapatan 6 Bulan Terakhir</h2>
            </div>
            <div class="p-6">
                <div class="h-[280px]"><canvas data-chart="{{ json_encode($incomeChart) }}"></canvas></div>
            </div>
        </div>

        <div class="card">
            <div class="border-b border-border px-6 py-4">
                <h2 class="text-base font-semibold">Status Kamar</h2>
            </div>
            <div class="p-6">
                <div class="h-[280px]"><canvas data-chart="{{ json_encode($occupancyChart) }}"></canvas></div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card">
            <div class="border-b border-border px-6 py-4">
                <h2 class="text-base font-semibold">Pembayaran Terbaru</h2>
            </div>
            <div class="p-2">
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th>Penghuni</th>
                                <th>Kamar</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentBills as $bill)
                                <tr>
                                    <td class="font-medium">{{ $bill->tenant?->nama ?? '-' }}</td>
                                    <td>{{ $bill->tenant?->room?->nomor ?? '-' }}</td>
                                    <td>Rp {{ number_format((float) $bill->total, 0, ',', '.') }}</td>
                                    <td><x-bill-status :status="$bill->status" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-8 text-center text-sm text-muted-foreground">Belum ada pembayaran.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="border-b border-border px-6 py-4">
                <h2 class="text-base font-semibold">Pengaduan Terbaru</h2>
            </div>
            <div class="p-2">
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th>Penghuni</th>
                                <th>Kamar</th>
                                <th>Masalah</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentComplaints as $complaint)
                                <tr>
                                    <td class="font-medium">{{ $complaint->tenant?->nama ?? '-' }}</td>
                                    <td>{{ $complaint->tenant?->room?->nomor ?? '-' }}</td>
                                    <td class="max-w-[150px] truncate">{{ $complaint->judul }}</td>
                                    <td><x-complaint-status :status="$complaint->status" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-8 text-center text-sm text-muted-foreground">Belum ada pengaduan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
