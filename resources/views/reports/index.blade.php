@extends('layouts.app')

@section('title', 'Laporan Keuangan — KosKu')

@section('content')
@php
    $incomeChart = [
        'type' => 'bar',
        'labels' => $monthly->pluck('name')->all(),
        'datasets' => [[
            'label' => 'Pendapatan',
            'data' => $monthly->pluck('pendapatan')->all(),
            'backgroundColor' => 'oklch(0.34 0.06 254)',
            'borderRadius' => 8,
            'maxBarThickness' => 36,
        ]],
    ];
    $statusChart = [
        'type' => 'pie',
        'labels' => collect($statusData)->pluck('name')->all(),
        'datasets' => [[
            'data' => collect($statusData)->pluck('value')->all(),
            'backgroundColor' => collect($statusData)->pluck('color')->all(),
            'borderWidth' => 0,
        ]],
        'legend' => true,
        'money' => false,
    ];
@endphp

<div class="space-y-6">
    <x-page-header title="Laporan Keuangan" subtitle="Analisis pemasukan dan tunggakan">
        <form method="GET" action="{{ route('reports.index') }}">
            <select name="tahun" class="select w-[120px]" onchange="this.form.submit()">
                @foreach ($years as $year)
                    <option value="{{ $year }}" @selected((int) $year === $tahun)>{{ $year }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('reports.export', ['tahun' => $tahun]) }}" class="btn btn-outline">
            <i data-lucide="download" class="h-4 w-4"></i> Export
        </a>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="card">
            <div class="flex items-center gap-4 p-5">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-success/15 text-success"><i data-lucide="trending-up" class="h-5 w-5"></i></div>
                <div><div class="text-xs uppercase text-muted-foreground">Total Pemasukan</div><div class="text-xl font-bold">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</div></div>
            </div>
        </div>
        <div class="card">
            <div class="flex items-center gap-4 p-5">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-destructive/15 text-destructive"><i data-lucide="alert-circle" class="h-5 w-5"></i></div>
                <div><div class="text-xs uppercase text-muted-foreground">Belum Lunas</div><div class="text-xl font-bold">Rp {{ number_format($totalBelumLunas, 0, ',', '.') }}</div></div>
            </div>
        </div>
        <div class="card">
            <div class="flex items-center gap-4 p-5">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary"><i data-lucide="users" class="h-5 w-5"></i></div>
                <div><div class="text-xs uppercase text-muted-foreground">Penghuni Aktif</div><div class="text-xl font-bold">{{ $aktif }}</div></div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="border-b border-border px-6 py-4">
                <h2 class="text-base font-semibold">Pemasukan Bulanan {{ $tahun }}</h2>
            </div>
            <div class="p-6">
                <div class="h-[300px]"><canvas data-chart="{{ json_encode($incomeChart) }}"></canvas></div>
            </div>
        </div>
        <div class="card">
            <div class="border-b border-border px-6 py-4">
                <h2 class="text-base font-semibold">Status Pembayaran</h2>
            </div>
            <div class="p-6">
                <div class="h-[300px]"><canvas data-chart="{{ json_encode($statusChart) }}"></canvas></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="border-b border-border px-6 py-4">
            <h2 class="text-base font-semibold">Pembayaran Terlambat</h2>
        </div>
        <div class="p-0">
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr><th>Penghuni</th><th>Periode</th><th>Total</th><th>Jatuh Tempo</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($late as $bill)
                            <tr>
                                <td class="font-medium">{{ $bill->tenant?->nama ?? '-' }}</td>
                                <td>{{ ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][$bill->bulan - 1] }} {{ $bill->tahun }}</td>
                                <td>Rp {{ number_format((float) $bill->total, 0, ',', '.') }}</td>
                                <td>{{ $bill->jatuh_tempo->locale('id')->translatedFormat('j M Y') }}</td>
                                <td><x-bill-status :status="$bill->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-sm text-muted-foreground">Tidak ada keterlambatan 🎉</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
