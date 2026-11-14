<?php

namespace Database\Factories;

use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MediaAsset> */
class MediaAssetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'disk' => 'public',
            'path' => 'uploads/'.fake()->uuid().'.jpg',
            'original_name' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
        ];
    }
}
