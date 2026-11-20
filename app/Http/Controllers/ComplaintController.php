<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    public function index(): View
    {
        $complaints = Complaint::with('tenant.room')
            ->orderByDesc('tanggal_lapor')->orderByDesc('id')
            ->get();
        $tenants = Tenant::with('room')->where('status', 'Aktif')->orderBy('nama')->get();
        $rooms = Room::orderBy('nomor')->get();

        return view('complaints.index', compact('complaints', 'tenants', 'rooms'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['tanggal_lapor'] = Carbon::today()->toDateString();

        $complaint = Complaint::create($data);
        $complaint->addEvent('Laporan masuk');

        return back()->with('success', 'Pengaduan dibuat.');
    }

    public function update(Request $request, Complaint $complaint): RedirectResponse
    {
        $data = $this->validated($request);
        $oldStatus = $complaint->status;

        if ($data['status'] === 'Selesai' && $oldStatus !== 'Selesai') {
            $data['tanggal_selesai'] = Carbon::today()->toDateString();
        }

        $complaint->update($data);

        if ($oldStatus !== $data['status']) {
            $complaint->addEvent('Status diubah ke '.$data['status']);
            if ($data['status'] === 'Selesai') {
                $complaint->addEvent('Pengaduan selesai ditangani');
            }
        }

        return back()->with('success', 'Pengaduan diperbarui.');
    }

    public function show(Request $request, Complaint $complaint): JsonResponse
    {
        $user = $request->user();

        if ($user->isPenghuni()) {
            abort_unless($user->tenant && $complaint->tenant_id === $user->tenant->id, 403);
        }

        $complaint->load(['tenant.room', 'events']);

        return response()->json([
            'id' => $complaint->id,
            'judul' => $complaint->judul,
            'deskripsi' => $complaint->deskripsi,
            'kategori' => $complaint->kategori,
            'prioritas' => $complaint->prioritas,
            'status' => $complaint->status,
            'catatan' => $complaint->catatan,
            'penghuni' => $complaint->tenant?->nama,
            'kamar' => $complaint->tenant?->room?->nomor,
            'tanggal_lapor' => $this->formatDate($complaint->tanggal_lapor),
            'tanggal_selesai' => $complaint->tanggal_selesai ? $this->formatDate($complaint->tanggal_selesai) : null,
            'timeline' => $complaint->events->map(fn ($event) => [
                'label' => $event->label,
                'tanggal' => $this->formatDate($event->tanggal),
            ])->values(),
        ]);
    }

    public function destroy(Complaint $complaint): RedirectResponse
    {
        $complaint->delete();

        return back()->with('success', 'Pengaduan dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'kategori' => ['required', Rule::in(Complaint::CATEGORIES)],
            'prioritas' => ['required', Rule::in(Complaint::PRIORITIES)],
            'judul' => ['required', 'string', 'min:3', 'max:180'],
            'deskripsi' => ['required', 'string', 'min:5'],
            'status' => ['required', Rule::in(Complaint::STATUSES)],
            'target_selesai' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string'],
        ]);
    }

    private function formatDate(Carbon $date): string
    {
        $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        return $date->day.' '.$bulan[$date->month - 1].' '.$date->year;
    }
}
