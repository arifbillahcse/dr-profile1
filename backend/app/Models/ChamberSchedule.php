<?php

namespace App\Models;

use Database\Factories\ChamberScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['chamber_id', 'day_of_week', 'opens_at', 'closes_at', 'is_active'])]
class ChamberSchedule extends Model
{
    /** @use HasFactory<ChamberScheduleFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function chamber(): BelongsTo
    {
        return $this->belongsTo(Chamber::class, 'chamber_id');
    }
}
