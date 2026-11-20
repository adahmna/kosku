<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MyComplaintController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $request->user()->tenant;
        abort_unless($tenant, 403, 'Akun penghuni belum terhubung dengan data penghuni.');

        $complaints = Complaint::where('tenant_id', $tenant->id)
            ->orderByDesc('tanggal_lapor')->orderByDesc('id')
            ->get();

        return view('my.complaints', compact('tenant', 'complaints'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $request->user()->tenant;
        abort_unless($tenant, 403, 'Akun penghuni belum terhubung dengan data penghuni.');

        $data = $request->validate([
            'kategori' => ['required', Rule::in(Complaint::CATEGORIES)],
            'prioritas' => ['required', Rule::in(Complaint::PRIORITIES)],
            'judul' => ['required', 'string', 'min:3', 'max:180'],
            'deskripsi' => ['required', 'string', 'min:5'],
        ]);

        $complaint = Complaint::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'status' => 'Baru',
            'tanggal_lapor' => Carbon::today()->toDateString(),
        ]);

        $complaint->addEvent('Laporan masuk');

        return back()->with('success', 'Pengaduan terkirim.');
    }
}
