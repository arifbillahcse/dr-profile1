<?php

namespace App\Models;

use Database\Factories\SiteSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['key', 'site_title', 'logo_image_id', 'favicon_image_id', 'social_image_id', 'phone', 'additional_phones', 'email', 'address', 'whatsapp_number', 'social_links', 'seo_title', 'seo_description', 'seo_keywords'])]
class SiteSetting extends Model
{
    /** @use HasFactory<SiteSettingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'additional_phones' => 'array',
            'social_links' => 'array',
        ];
    }

    public function logo(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'logo_image_id');
    }

    public function favicon(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'favicon_image_id');
    }

    public function socialImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'social_image_id');
    }
}
