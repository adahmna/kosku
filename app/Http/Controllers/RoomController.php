<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(): View
    {
        $rooms = Room::orderBy('nomor')->get();
        $lantai = $rooms->pluck('lantai')->unique()->sort()->values();

        return view('rooms.index', compact('rooms', 'lantai'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('rooms', 'public');
        }
        Room::create($data);

        return back()->with('success', 'Kamar berhasil ditambahkan.');
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $data = $this->validated($request, $room);
        if ($request->hasFile('foto')) {
            if ($room->foto) {
                Storage::disk('public')->delete($room->foto);
            }
            $data['foto'] = $request->file('foto')->store('rooms', 'public');
        }
        $room->update($data);

        return back()->with('success', 'Kamar berhasil diperbarui.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        if ($room->activeTenants()->exists()) {
            return back()->with('error', 'Kamar tidak bisa dihapus karena masih ada penghuni aktif.');
        }

        $room->delete();

        return back()->with('success', 'Kamar dihapus.');
    }

    private function validated(Request $request, ?Room $room = null): array
    {
        $data = $request->validate([
            'nomor' => ['required', 'string', 'max:30', Rule::unique('rooms', 'nomor')->ignore($room)],
            'tipe' => ['required', Rule::in(Room::TYPES)],
            'lantai' => ['required', 'integer', 'min:1'],
            'harga' => ['required', 'numeric', 'min:1'],
            'luas' => ['required', 'numeric', 'min:1'],
            'status' => ['required', Rule::in(Room::STATUSES)],
            'fasilitas' => ['nullable', 'array'],
            'fasilitas.*' => ['string', 'max:50'],
            'deskripsi' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ]);

        unset($data['foto']);

        $data['fasilitas'] = array_values($data['fasilitas'] ?? []);

        return $data;
    }
}
