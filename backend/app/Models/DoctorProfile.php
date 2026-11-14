<?php

namespace App\Models;

use Database\Factories\DoctorProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['key', 'name', 'designation', 'specialty', 'trusted_patients', 'credentials', 'summary', 'biography', 'introduction_youtube_id', 'portrait_image_id', 'first_image_id', 'second_image_id', 'tall_image_id'])]
class DoctorProfile extends Model
{
    /** @use HasFactory<DoctorProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'credentials' => 'array',
        ];
    }

    public function portrait(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'portrait_image_id');
    }

    public function firstImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'first_image_id');
    }

    public function secondImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'second_image_id');
    }

    public function tallImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'tall_image_id');
    }
}
