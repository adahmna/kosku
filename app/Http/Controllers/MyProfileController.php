<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MyProfileController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $request->user()->tenant;
        abort_unless($tenant, 403, 'Akun penghuni belum terhubung dengan data penghuni.');

        $tenant->load('room');
        $sisaHari = (int) now()->startOfDay()->diffInDays($tenant->tanggal_berakhir->startOfDay(), false);

        return view('my.profile', compact('tenant', 'sisaHari'));
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = $request->user()->tenant;
        abort_unless($tenant, 403, 'Akun penghuni belum terhubung dengan data penghuni.');

        $data = $request->validate([
            'hp' => ['required', 'string', 'min:8', 'max:32'],
            'email' => [
                'required',
                'email',
                'max:120',
                Rule::unique('users', 'email')->ignore($request->user()->id),
                Rule::unique('tenants', 'email')->ignore($tenant->id),
            ],
        ]);

        DB::transaction(function () use ($request, $tenant, $data) {
            $tenant->update($data);
            $request->user()->update(['email' => $data['email']]);
        });

        return back()->with('success', 'Profil dan email login diperbarui.');
    }
}
