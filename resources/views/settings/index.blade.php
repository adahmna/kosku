@extends('layouts.app')

@section('title', 'Pengaturan — KosKu')

@section('content')
<div class="space-y-6" x-data="{ tab: 'profil', userModal: false }">
    <x-page-header title="Pengaturan" subtitle="Konfigurasi sistem dan profil kos" />

    <div class="card">
        <div class="flex flex-wrap gap-1 border-b border-border p-2">
            <button type="button" class="btn btn-sm" :class="tab === 'profil' ? 'bg-primary text-primary-foreground' : 'btn-ghost'" @click="tab = 'profil'">Profil Kos</button>
            <button type="button" class="btn btn-sm" :class="tab === 'tagihan' ? 'bg-primary text-primary-foreground' : 'btn-ghost'" @click="tab = 'tagihan'">Pengaturan Tagihan</button>
            <button type="button" class="btn btn-sm" :class="tab === 'user' ? 'bg-primary text-primary-foreground' : 'btn-ghost'" @click="tab = 'user'">Manajemen User</button>
        </div>

        <div x-show="tab === 'profil'" x-cloak class="p-6">
            <form action="{{ route('settings.profile') }}" method="POST" class="space-y-4">
                @csrf
                @method('PATCH')
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Nama Kos</label><input type="text" name="nama_kos" value="{{ $settings['nama_kos'] ?? '' }}" class="input" required></div>
                    <div><label class="label">No. HP</label><input type="text" name="no_hp" value="{{ $settings['no_hp'] ?? '' }}" class="input" required></div>
                    <div class="sm:col-span-2"><label class="label">Alamat Lengkap</label><textarea name="alamat" class="textarea" required>{{ $settings['alamat'] ?? '' }}</textarea></div>
                    <div><label class="label">Email</label><input type="email" name="email" value="{{ $settings['email'] ?? '' }}" class="input" required></div>
                    <div><label class="label">Logo</label><input type="file" accept="image/*" class="input py-1.5"></div>
                    <div class="sm:col-span-2"><label class="label">Deskripsi</label><textarea name="deskripsi" rows="3" class="textarea">{{ $settings['deskripsi'] ?? '' }}</textarea></div>
                </div>
                <div class="flex justify-end"><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>

        <div x-show="tab === 'tagihan'" x-cloak class="p-6">
            <form action="{{ route('settings.billing') }}" method="POST" class="space-y-5">
                @csrf
                @method('PATCH')
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Tanggal Generate Tagihan Otomatis</label><input type="number" name="tgl_generate" min="1" max="28" value="{{ $settings['tgl_generate'] ?? 1 }}" class="input" required></div>
                    <div><label class="label">Jatuh Tempo Default (tanggal)</label><input type="number" name="jatuh_tempo" min="1" max="28" value="{{ $settings['jatuh_tempo'] ?? 10 }}" class="input" required></div>
                    <div><label class="label">Denda Keterlambatan (% per hari)</label><input type="number" name="denda_persen" min="0" step="0.1" value="{{ $settings['denda_persen'] ?? 1 }}" class="input" required></div>
                    <div><label class="label">Atau Denda Flat (Rp)</label><input type="number" name="denda_flat" min="0" value="{{ $settings['denda_flat'] ?? 10000 }}" class="input" required></div>
                </div>
                <div class="flex items-center justify-between rounded-lg border border-border p-4">
                    <div><div class="font-medium">Pengingat Tagihan Otomatis</div><div class="text-xs text-muted-foreground">Kirim pengingat 3 hari sebelum jatuh tempo</div></div>
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input type="checkbox" name="pengingat_otomatis" value="1" class="peer sr-only" @checked(($settings['pengingat_otomatis'] ?? '0') === '1')>
                        <span class="relative h-6 w-11 rounded-full bg-muted transition peer-checked:bg-primary peer-focus:ring-2 peer-focus:ring-ring peer-focus:ring-offset-1 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5"></span>
                    </label>
                </div>
                <div class="flex items-center justify-between rounded-lg border border-border p-4">
                    <div><div class="font-medium">Notifikasi WhatsApp</div><div class="text-xs text-muted-foreground">Kirim notifikasi via WhatsApp</div></div>
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input type="checkbox" name="notifikasi_wa" value="1" class="peer sr-only" @checked(($settings['notifikasi_wa'] ?? '0') === '1')>
                        <span class="relative h-6 w-11 rounded-full bg-muted transition peer-checked:bg-primary peer-focus:ring-2 peer-focus:ring-ring peer-focus:ring-offset-1 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5"></span>
                    </label>
                </div>
                <div class="flex justify-end"><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>

        <div x-show="tab === 'user'" x-cloak>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td class="font-medium">{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ ucfirst($user->role) }}</td>
                                <td><x-status-pill :variant="$user->aktif ? 'success' : 'default'">{{ $user->aktif ? 'Aktif' : 'Nonaktif' }}</x-status-pill></td>
                                <td class="text-right">
                                    @if (! $user->is(auth()->user()))
                                        <form action="{{ route('settings.users.toggle', $user) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-outline btn-sm">{{ $user->aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-muted-foreground">Akun Anda</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-border p-4">
                <button type="button" class="btn btn-outline" @click="userModal = true">+ Tambah User</button>
            </div>
        </div>
    </div>

    <div x-show="userModal" x-cloak class="modal-backdrop" @click="userModal = false"></div>
    <div x-show="userModal" x-cloak x-transition class="modal-panel max-w-md">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold">Tambah User</h2>
                <p class="mt-1 text-sm text-muted-foreground">Buat akun baru untuk mengakses sistem.</p>
            </div>
            <button type="button" class="btn btn-ghost btn-icon -mr-2 -mt-2" @click="userModal = false"><i data-lucide="x" class="h-4 w-4"></i></button>
        </div>
        <form action="{{ route('settings.users.store') }}" method="POST" class="space-y-4">
            @csrf
            <div><label class="label">Nama</label><input type="text" name="name" class="input" required></div>
            <div><label class="label">Email</label><input type="email" name="email" class="input" required></div>
            <div><label class="label">Password</label><input type="password" name="password" class="input" minlength="8" required></div>
            <div>
                <label class="label">Role</label>
                <select name="role" class="select">
                    <option value="pemilik">Pemilik</option>
                    <option value="penghuni">Penghuni</option>
                </select>
                <p class="mt-1 text-xs text-muted-foreground">Akun penghuni dibuat otomatis saat menambahkan data penghuni.</p>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn btn-outline" @click="userModal = false">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
