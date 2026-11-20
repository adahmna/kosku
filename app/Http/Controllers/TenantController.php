<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::with('room')->orderBy('nama')->get();
        $rooms = Room::orderBy('nomor')->get();

        return view('tenants.index', compact('tenants', 'rooms'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['tanggal_berakhir'] = Carbon::parse($data['tanggal_masuk'])
            ->addMonthsNoOverflow((int) $data['durasi_bulan'])->toDateString();
        $password = $data['password'] ?? null;
        unset($data['password']);

        $tenant = DB::transaction(function () use ($data, $password) {
            $tenant = Tenant::create($data);
            $this->syncPenghuniAccount($tenant, $password);

            return $tenant;
        });

        $this->syncRoomStatus($tenant->room_id);

        return back()->with('success', 'Penghuni dan akun login berhasil dibuat.');
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $this->validated($request, $tenant);
        $data['tanggal_berakhir'] = Carbon::parse($data['tanggal_masuk'])
            ->addMonthsNoOverflow((int) $data['durasi_bulan'])->toDateString();
        $password = $data['password'] ?? null;
        unset($data['password']);

        $oldRoomId = $tenant->room_id;

        DB::transaction(function () use ($tenant, $data, $password) {
            $tenant->update($data);
            $this->syncPenghuniAccount($tenant, $password);
        });

        $this->syncRoomStatus($oldRoomId);
        $this->syncRoomStatus($tenant->room_id);

        return back()->with('success', 'Penghuni dan akun login diperbarui.');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $roomId = $tenant->room_id;
        $tenant->user?->update(['aktif' => false]);
        $tenant->delete();
        $this->syncRoomStatus($roomId);

        return back()->with('success', 'Penghuni dihapus.');
    }

    private function validated(Request $request, ?Tenant $tenant = null): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'min:2', 'max:120'],
            'nik' => ['required', 'string', 'min:8', 'max:32'],
            'hp' => ['required', 'string', 'min:8', 'max:32'],
            'email' => ['required', 'email', 'max:120', Rule::unique('tenants', 'email')->ignore($tenant?->id)],
            'password' => ['nullable', 'string', 'min:8', 'max:72'],
            'alamat' => ['required', 'string', 'min:3'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'tanggal_masuk' => ['required', 'date'],
            'durasi_bulan' => ['required', 'integer', 'min:1', 'max:60'],
            'deposit' => ['required', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['Aktif', 'Tidak Aktif'])],
        ]);

        if (($data['status'] ?? null) === 'Aktif' && ! empty($data['room_id'])) {
            $occupied = Tenant::where('room_id', $data['room_id'])
                ->where('status', 'Aktif')
                ->when($tenant, fn ($query) => $query->whereKeyNot($tenant->id))
                ->exists();

            if ($occupied) {
                throw ValidationException::withMessages([
                    'room_id' => 'Kamar tersebut sudah ditempati penghuni aktif lain.',
                ]);
            }
        }

        return $data;
    }

    private function syncPenghuniAccount(Tenant $tenant, ?string $password): void
    {
        $email = strtolower(trim($tenant->email));
        $user = $tenant->user;

        if ($user) {
            if (strcasecmp($user->email, $email) !== 0) {
                $taken = User::whereRaw('LOWER(email) = ?', [$email])
                    ->whereKeyNot($user->id)
                    ->exists();

                if ($taken) {
                    throw ValidationException::withMessages([
                        'email' => 'Email ini sudah digunakan akun lain.',
                    ]);
                }

                $user->email = $email;
            }

            if ($password) {
                $user->password = $password;
            }

            $user->save();

            return;
        }

        $existing = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($existing) {
            if (! $existing->isPenghuni()) {
                throw ValidationException::withMessages([
                    'email' => 'Email ini sudah digunakan akun pemilik.',
                ]);
            }

            if ($existing->tenant) {
                throw ValidationException::withMessages([
                    'email' => 'Email ini sudah terhubung dengan penghuni lain.',
                ]);
            }

            $existing->name = $tenant->nama;
            $existing->role = 'penghuni';
            $existing->aktif = true;

            if ($password) {
                $existing->password = $password;
            }

            $existing->save();
            $user = $existing;
        } else {
            $user = User::create([
                'name' => $tenant->nama,
                'email' => $email,
                'password' => $password ?: $tenant->defaultPassword(),
                'role' => 'penghuni',
                'aktif' => true,
            ]);
        }

        $tenant->update(['user_id' => $user->id]);
    }

    private function syncRoomStatus(?int $roomId): void
    {
        if (! $roomId) {
            return;
        }

        $room = Room::find($roomId);
        if (! $room) {
            return;
        }

        $hasActiveTenant = $room->activeTenants()->exists();

        if ($hasActiveTenant) {
            $room->update(['status' => 'Terisi']);
        } elseif ($room->status !== 'Dalam Perawatan') {
            $room->update(['status' => 'Tersedia']);
        }
    }
}
