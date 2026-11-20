<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintEvent extends Model
{
    protected $fillable = ['complaint_id', 'tanggal', 'label'];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }
}
