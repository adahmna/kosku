<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'room_id', 'nama', 'nik', 'hp', 'email', 'alamat',
        'tanggal_masuk', 'durasi_bulan', 'tanggal_berakhir', 'deposit',
        'catatan', 'status', 'foto',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'date',
            'tanggal_berakhir' => 'date',
            'durasi_bulan' => 'integer',
            'deposit' => 'decimal:2',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function defaultPassword(): string
    {
        $this->loadMissing('room');

        $name = Str::slug($this->nama, '');
        $room = Str::slug($this->room?->nomor ?? 'kosku', '');

        return ($name !== '' ? $name : 'penghuni').$room;
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }
}
