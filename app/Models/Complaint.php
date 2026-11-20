<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Complaint extends Model
{
    use HasFactory;

    public const STATUSES = ['Baru', 'Diproses', 'Selesai', 'Ditolak'];

    public const PRIORITIES = ['Tinggi', 'Sedang', 'Rendah'];

    public const CATEGORIES = ['Fasilitas Rusak', 'Kebersihan', 'Keamanan', 'Kebisingan', 'Listrik & Air', 'Lainnya'];

    protected $fillable = [
        'tenant_id', 'kategori', 'prioritas', 'judul', 'deskripsi', 'status',
        'tanggal_lapor', 'tanggal_selesai', 'target_selesai', 'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lapor' => 'date',
            'tanggal_selesai' => 'date',
            'target_selesai' => 'date',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ComplaintEvent::class)->orderBy('tanggal')->orderBy('id');
    }

    public function addEvent(string $label, ?string $date = null): void
    {
        $this->events()->create([
            'tanggal' => $date ?? now()->toDateString(),
            'label' => $label,
        ]);
    }
}
