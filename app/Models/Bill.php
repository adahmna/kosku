<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bill extends Model
{
    use HasFactory;

    public const STATUSES = ['Lunas', 'Belum Lunas', 'Terlambat'];

    public const METHODS = ['Transfer Bank', 'Cash', 'QRIS'];

    protected $fillable = [
        'tenant_id', 'bulan', 'tahun', 'sewa', 'listrik', 'air', 'tambahan',
        'ket_tambahan', 'total', 'jatuh_tempo', 'status', 'tanggal_bayar',
        'metode', 'bukti_bayar',
    ];

    protected function casts(): array
    {
        return [
            'bulan' => 'integer',
            'tahun' => 'integer',
            'sewa' => 'decimal:2',
            'listrik' => 'decimal:2',
            'air' => 'decimal:2',
            'tambahan' => 'decimal:2',
            'total' => 'decimal:2',
            'jatuh_tempo' => 'date',
            'tanggal_bayar' => 'date',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
