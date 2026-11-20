@extends('layouts.app')

@section('title', 'Profil Saya — KosKu')

@section('content')
@php
    $initials = collect(explode(' ', $tenant->nama))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('');
    $room = $tenant->room;
@endphp

<div class="space-y-6" x-data="{ edit: false }">
    <x-page-header title="Profil Saya" subtitle="Informasi pribadi dan kontrak">
        <button type="button" class="btn btn-outline" x-show="!edit" @click="edit = true">
            <i data-lucide="pencil" class="h-4 w-4"></i> Edit Profil
        </button>
        <button type="submit" form="profile-form" class="btn btn-primary" x-show="edit" x-cloak>
            <i data-lucide="save" class="h-4 w-4"></i> Simpan
        </button>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-1">
            <div class="flex flex-col items-center gap-3 p-6 text-center">
                <div class="flex h-24 w-24 items-center justify-center rounded-full bg-primary text-2xl font-semibold text-primary-foreground">{{ strtoupper($initials) }}</div>
                <div>
                    <div class="text-xl font-bold">{{ $tenant->nama }}</div>
                    <div class="text-sm text-muted-foreground">{{ $tenant->email }}</div>
                </div>
                <div class="mt-2 w-full space-y-2 rounded-lg border border-border p-3 text-left text-sm">
                    <div class="flex justify-between"><span class="text-muted-foreground">NIK</span><span class="font-mono text-xs">{{ $tenant->nik }}</span></div>
                    <div class="flex justify-between"><span class="text-muted-foreground">Status</span><span class="font-medium">{{ $tenant->status }}</span></div>
                </div>
            </div>
        </div>

        <div class="space-y-6 lg:col-span-2">
            <div class="card">
                <form id="profile-form" action="{{ route('my.profile.update') }}" method="POST" class="space-y-4 p-6">
                    @csrf
                    @method('PATCH')
                    <div class="text-sm font-semibold uppercase text-muted-foreground">Kontak</div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">No. HP / WhatsApp</label>
                            <input type="text" name="hp" value="{{ $tenant->hp }}" class="input" :disabled="!edit" required>
                        </div>
                        <div>
                            <label class="label">Email</label>
                            <input type="email" name="email" value="{{ $tenant->email }}" class="input" :disabled="!edit" required>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label">Alamat Asal</label>
                            <input type="text" value="{{ $tenant->alamat }}" class="input" disabled>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="space-y-3 p-6">
                    <div class="text-sm font-semibold uppercase text-muted-foreground">Kamar & Kontrak</div>
                    <div class="grid gap-3 text-sm sm:grid-cols-2">
                        <div><div class="text-xs text-muted-foreground">Kamar</div><div class="font-medium">{{ $room?->nomor ?? '-' }} ({{ $room?->tipe }})</div></div>
                        <div><div class="text-xs text-muted-foreground">Harga / Bulan</div><div class="font-medium">{{ $room ? 'Rp '.number_format((float) $room->harga, 0, ',', '.') : '-' }}</div></div>
                        <div><div class="text-xs text-muted-foreground">Tanggal Masuk</div><div class="font-medium">{{ $tenant->tanggal_masuk->locale('id')->translatedFormat('j F Y') }}</div></div>
                        <div><div class="text-xs text-muted-foreground">Tanggal Berakhir</div><div class="font-medium">{{ $tenant->tanggal_berakhir->locale('id')->translatedFormat('j F Y') }}</div></div>
                        <div><div class="text-xs text-muted-foreground">Durasi Kontrak</div><div class="font-medium">{{ $tenant->durasi_bulan }} bulan</div></div>
                        <div><div class="text-xs text-muted-foreground">Sisa Hari</div><div class="font-bold text-primary">{{ $sisaHari }} hari</div></div>
                        <div><div class="text-xs text-muted-foreground">Deposit</div><div class="font-medium">Rp {{ number_format((float) $tenant->deposit, 0, ',', '.') }}</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
