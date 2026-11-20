<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    public const STATUSES = ['Tersedia', 'Terisi', 'Dalam Perawatan'];

    public const TYPES = ['Standard', 'Deluxe', 'VIP'];

    protected $fillable = [
        'nomor', 'tipe', 'lantai', 'harga', 'luas', 'fasilitas',
        'status', 'deskripsi', 'foto',
    ];

    protected function casts(): array
    {
        return [
            'lantai' => 'integer',
            'harga' => 'decimal:2',
            'luas' => 'integer',
            'fasilitas' => 'array',
        ];
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function activeTenants(): HasMany
    {
        return $this->hasMany(Tenant::class)->where('status', 'Aktif');
    }
}
