<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingController extends Controller
{
    private const PROFILE_KEYS = ['nama_kos', 'no_hp', 'alamat', 'email', 'deskripsi'];

    private const BILLING_KEYS = ['tgl_generate', 'jatuh_tempo', 'denda_persen', 'denda_flat', 'pengingat_otomatis', 'notifikasi_wa'];

    public function index(): View
    {
        $settings = Setting::many([...self::PROFILE_KEYS, ...self::BILLING_KEYS]);
        $users = User::orderByRaw("CASE role WHEN 'pemilik' THEN 1 ELSE 2 END")->orderBy('name')->get();

        return view('settings.index', compact('settings', 'users'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_kos' => ['required', 'string', 'max:120'],
            'no_hp' => ['required', 'string', 'max:32'],
            'alamat' => ['required', 'string', 'max:500'],
            'email' => ['required', 'email', 'max:120'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ($data as $key => $value) {
            Setting::put($key, $value);
        }

        return back()->with('success', 'Profil kos disimpan.');
    }

    public function updateBilling(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tgl_generate' => ['required', 'integer', 'between:1,28'],
            'jatuh_tempo' => ['required', 'integer', 'between:1,28'],
            'denda_persen' => ['required', 'numeric', 'min:0'],
            'denda_flat' => ['required', 'numeric', 'min:0'],
            'pengingat_otomatis' => ['nullable', 'boolean'],
            'notifikasi_wa' => ['nullable', 'boolean'],
        ]);

        foreach ($data as $key => $value) {
            Setting::put($key, (string) ($value ?? 0));
        }

        Setting::put('pengingat_otomatis', $request->boolean('pengingat_otomatis') ? '1' : '0');
        Setting::put('notifikasi_wa', $request->boolean('notifikasi_wa') ? '1' : '0');

        return back()->with('success', 'Pengaturan tagihan disimpan.');
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['pemilik', 'penghuni'])],
        ]);

        $tenant = null;
        if ($data['role'] === 'penghuni') {
            $tenant = Tenant::where('email', $data['email'])->first();

            if (! $tenant) {
                throw ValidationException::withMessages([
                    'email' => 'Data penghuni dengan email ini belum ada. Tambahkan penghuni terlebih dahulu.',
                ]);
            }

            if ($tenant->user_id) {
                throw ValidationException::withMessages([
                    'email' => 'Penghuni ini sudah memiliki akun.',
                ]);
            }
        }

        $user = User::create([...$data, 'aktif' => true]);

        if ($tenant) {
            $tenant->update(['user_id' => $user->id]);
        }

        return back()->with('success', 'User baru berhasil ditambahkan.');
    }

    public function toggleUser(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $user->update(['aktif' => ! $user->aktif]);

        return back()->with('success', 'Status user diperbarui.');
    }
}
