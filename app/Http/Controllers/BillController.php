<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BillController extends Controller
{
    public function index(): View
    {
        $bills = Bill::with('tenant.room')
            ->orderByDesc('tahun')->orderByDesc('bulan')->orderByDesc('id')
            ->get();
        $tenants = Tenant::with('room')->where('status', 'Aktif')->orderBy('nama')->get();
        $rooms = Room::orderBy('nomor')->get();

        return view('bills.index', compact('bills', 'tenants', 'rooms'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Bill::create($this->normalize($data));

        return back()->with('success', 'Tagihan dibuat.');
    }

    public function update(Request $request, Bill $bill): RedirectResponse
    {
        $data = $this->validated($request, $bill);
        $bill->update($this->normalize($data, $bill));

        return back()->with('success', 'Tagihan diperbarui.');
    }

    public function destroy(Bill $bill): RedirectResponse
    {
        $bill->delete();

        return back()->with('success', 'Tagihan dihapus.');
    }

    public function generate(): RedirectResponse
    {
        $now = Carbon::now()->startOfMonth();
        $created = 0;

        DB::transaction(function () use ($now, &$created) {
            $tenants = Tenant::with('room')
                ->where('status', 'Aktif')
                ->whereNotNull('room_id')
                ->get();

            foreach ($tenants as $tenant) {
                if (! $tenant->room) {
                    continue;
                }

                $exists = Bill::where('tenant_id', $tenant->id)
                    ->where('bulan', $now->month)
                    ->where('tahun', $now->year)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $listrik = 100000;
                $air = 35000;

                Bill::create([
                    'tenant_id' => $tenant->id,
                    'bulan' => $now->month,
                    'tahun' => $now->year,
                    'sewa' => $tenant->room->harga,
                    'listrik' => $listrik,
                    'air' => $air,
                    'tambahan' => 0,
                    'ket_tambahan' => '',
                    'total' => (float) $tenant->room->harga + $listrik + $air,
                    'jatuh_tempo' => $now->copy()->day(10)->toDateString(),
                    'status' => 'Belum Lunas',
                ]);

                $created++;
            }
        });

        return back()->with('success', $created > 0
            ? $created.' tagihan berhasil dibuat.'
            : 'Semua penghuni aktif sudah memiliki tagihan bulan ini.');
    }

    private function validated(Request $request, ?Bill $bill = null): array
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
            'sewa' => ['required', 'numeric', 'min:0'],
            'listrik' => ['required', 'numeric', 'min:0'],
            'air' => ['required', 'numeric', 'min:0'],
            'tambahan' => ['required', 'numeric', 'min:0'],
            'ket_tambahan' => ['nullable', 'string', 'max:255'],
            'jatuh_tempo' => ['required', 'date'],
            'status' => ['required', Rule::in(Bill::STATUSES)],
            'tanggal_bayar' => ['nullable', 'date'],
            'metode' => ['nullable', Rule::in(Bill::METHODS)],
        ]);

        $duplicate = Bill::where('tenant_id', $data['tenant_id'])
            ->where('bulan', $data['bulan'])
            ->where('tahun', $data['tahun'])
            ->when($bill, fn ($query) => $query->whereKeyNot($bill->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'bulan' => 'Tagihan untuk penghuni dan periode ini sudah ada.',
            ]);
        }

        return $data;
    }

    private function normalize(array $data, ?Bill $bill = null): array
    {
        $data['ket_tambahan'] = $data['ket_tambahan'] ?? '';
        $data['total'] = (float) $data['sewa'] + (float) $data['listrik'] + (float) $data['air'] + (float) $data['tambahan'];

        if ($data['status'] === 'Lunas') {
            $data['tanggal_bayar'] = $data['tanggal_bayar'] ?? $bill?->tanggal_bayar?->toDateString() ?? Carbon::today()->toDateString();
            $data['metode'] = $data['metode'] ?? $bill?->metode ?? 'Transfer Bank';
        } else {
            $data['tanggal_bayar'] = null;
            $data['metode'] = null;
        }

        return $data;
    }
}
