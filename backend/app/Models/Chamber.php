<?php

namespace App\Models;

use Database\Factories\ChamberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'address', 'phone', 'additional_phones', 'map_url', 'timezone', 'sort_order', 'is_active'])]
class Chamber extends OrderedContent
{
    /** @use HasFactory<ChamberFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'additional_phones' => 'array',
        ]);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ChamberSchedule::class)->orderBy('day_of_week')->orderBy('opens_at');
    }
}
