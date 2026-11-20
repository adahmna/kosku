<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    public function index(Request $request): View
    {
        $selectedYear = (int) $request->query('tahun', Carbon::now()->year);
        $data = $this->reportData($selectedYear);

        return view('reports.index', [...$data, 'tahun' => $selectedYear]);
    }

    public function export(Request $request): StreamedResponse
    {
        $selectedYear = (int) $request->query('tahun', Carbon::now()->year);
        $bills = Bill::with('tenant.room')
            ->where('tahun', $selectedYear)
            ->orderBy('bulan')->orderBy('id')
            ->get();

        return response()->streamDownload(function () use ($bills) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Penghuni', 'Kamar', 'Periode', 'Sewa', 'Listrik', 'Air', 'Tambahan', 'Total', 'Jatuh Tempo', 'Status', 'Tanggal Bayar', 'Metode']);

            foreach ($bills as $bill) {
                fputcsv($handle, [
                    $bill->tenant?->nama ?? '-',
                    $bill->tenant?->room?->nomor ?? '-',
                    self::BULAN[$bill->bulan - 1].' '.$bill->tahun,
                    $bill->sewa,
                    $bill->listrik,
                    $bill->air,
                    $bill->tambahan,
                    $bill->total,
                    $bill->jatuh_tempo->toDateString(),
                    $bill->status,
                    $bill->tanggal_bayar?->toDateString() ?? '-',
                    $bill->metode ?? '-',
                ]);
            }

            fclose($handle);
        }, 'laporan-keuangan-'.$selectedYear.'.csv', ['Content-Type' => 'text/csv']);
    }

    private function reportData(int $selectedYear): array
    {
        $bills = Bill::with('tenant.room')->where('tahun', $selectedYear)->get();
        $years = Bill::query()->select('tahun')->distinct()->orderBy('tahun')->pluck('tahun')
            ->push(Carbon::now()->year)->unique()->sort()->values();

        $totalPemasukan = (float) $bills->where('status', 'Lunas')->sum('total');
        $totalBelumLunas = (float) $bills->where('status', '!=', 'Lunas')->sum('total');
        $aktif = Tenant::where('status', 'Aktif')->count();

        $monthly = collect(range(0, 11))->map(function ($index) use ($bills) {
            $month = $index + 1;

            return [
                'name' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'][$index],
                'pendapatan' => (float) $bills->where('bulan', $month)->where('status', 'Lunas')->sum('total'),
            ];
        })->values();

        $statusData = [
            ['name' => 'Lunas', 'value' => $bills->where('status', 'Lunas')->count(), 'color' => 'oklch(0.65 0.17 150)'],
            ['name' => 'Belum Lunas', 'value' => $bills->where('status', 'Belum Lunas')->count(), 'color' => 'oklch(0.78 0.16 75)'],
            ['name' => 'Terlambat', 'value' => $bills->where('status', 'Terlambat')->count(), 'color' => 'oklch(0.6 0.22 27)'],
        ];

        $late = Bill::with('tenant.room')->where('status', 'Terlambat')->orderBy('tahun')->orderBy('bulan')->get();

        return compact('bills', 'years', 'totalPemasukan', 'totalBelumLunas', 'aktif', 'monthly', 'statusData', 'late');
    }
}
