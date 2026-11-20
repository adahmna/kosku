<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MyBillController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $request->user()->tenant;
        abort_unless($tenant, 403, 'Akun penghuni belum terhubung dengan data penghuni.');

        $bills = Bill::where('tenant_id', $tenant->id)
            ->orderByDesc('tahun')->orderByDesc('bulan')->orderByDesc('id')
            ->get();

        $now = Carbon::now();
        $current = $bills->first(fn (Bill $bill) => $bill->bulan === $now->month && $bill->tahun === $now->year)
            ?? $bills->first();

        return view('my.bills', compact('tenant', 'bills', 'current', 'now'));
    }

    public function uploadProof(Request $request, Bill $bill): RedirectResponse
    {
        $tenant = $request->user()->tenant;
        abort_unless($tenant && $bill->tenant_id === $tenant->id, 403);

        $request->validate([
            'bukti_bayar' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);

        if ($bill->bukti_bayar) {
            Storage::disk('public')->delete($bill->bukti_bayar);
        }

        $path = $request->file('bukti_bayar')->store('bukti-bayar', 'public');
        $bill->update(['bukti_bayar' => $path]);

        return back()->with('success', 'Bukti bayar berhasil diunggah, menunggu verifikasi pemilik.');
    }
}
