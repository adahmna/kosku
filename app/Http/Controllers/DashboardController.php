<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Complaint;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isPenghuni()) {
            return $this->penghuniDashboard($user);
        }

        return $this->ownerDashboard();
    }

    private function ownerDashboard(): View
    {
        $now = Carbon::now();
        $rooms = Room::all();
        $total = $rooms->count();
        $terisi = $rooms->where('status', 'Terisi')->count();
        $kosong = $rooms->where('status', 'Tersedia')->count();
        $perawatan = $rooms->where('status', 'Dalam Perawatan')->count();

        $belumLunasTotal = (float) Bill::where('status', '!=', 'Lunas')->sum('total');
        $pengaduanAktif = Complaint::whereIn('status', ['Baru', 'Diproses'])->count();
        $pendapatanBulanIni = (float) Bill::where('status', 'Lunas')
            ->where('bulan', $now->month)
            ->where('tahun', $now->year)
            ->sum('total');

        $incomeData = collect(range(5, 0))->map(function ($ago) use ($now) {
            $date = $now->copy()->startOfMonth()->subMonths($ago);
            $total = (float) Bill::where('status', 'Lunas')
                ->where('bulan', $date->month)
                ->where('tahun', $date->year)
                ->sum('total');

            return [
                'name' => $this->shortMonth($date),
                'pendapatan' => $total,
            ];
        })->values();

        $occupancyData = [
            ['name' => 'Terisi', 'value' => $terisi, 'color' => 'oklch(0.34 0.06 254)'],
            ['name' => 'Kosong', 'value' => $kosong, 'color' => 'oklch(0.65 0.17 150)'],
            ['name' => 'Perawatan', 'value' => $perawatan, 'color' => 'oklch(0.78 0.16 75)'],
        ];

        $recentBills = Bill::with('tenant.room')
            ->orderByDesc('tahun')->orderByDesc('bulan')->orderByDesc('id')
            ->limit(5)->get();

        $recentComplaints = Complaint::with('tenant.room')
            ->orderByDesc('tanggal_lapor')->orderByDesc('id')
            ->limit(5)->get();

        return view('dashboard.owner', compact(
            'total', 'terisi', 'kosong', 'perawatan', 'belumLunasTotal',
            'pengaduanAktif', 'pendapatanBulanIni', 'incomeData', 'occupancyData',
            'recentBills', 'recentComplaints', 'now'
        ));
    }

    private function penghuniDashboard($user): View
    {
        $tenant = $user->tenant;

        abort_unless($tenant, 403, 'Akun penghuni belum terhubung dengan data penghuni.');

        $now = Carbon::now();
        $room = $tenant->room;
        $myBills = Bill::where('tenant_id', $tenant->id)
            ->orderByDesc('tahun')->orderByDesc('bulan')->orderByDesc('id')
            ->get();

        $currentBill = $myBills->first(fn (Bill $bill) => $bill->bulan === $now->month && $bill->tahun === $now->year)
            ?? $myBills->first();

        $myComplaints = Complaint::where('tenant_id', $tenant->id)
            ->orderByDesc('tanggal_lapor')->orderByDesc('id')
            ->get();

        $sisaHari = (int) $now->startOfDay()->diffInDays($tenant->tanggal_berakhir->startOfDay(), false);

        return view('dashboard.penghuni', compact(
            'tenant', 'room', 'myBills', 'currentBill', 'myComplaints', 'sisaHari', 'now'
        ));
    }

    private function shortMonth(Carbon $date): string
    {
        return ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'][$date->month - 1];
    }
}
