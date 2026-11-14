<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class MediaLibrary
{
    public function store(UploadedFile $file, ?string $altText, ?User $user = null): MediaAsset
    {
        $attributes = $this->writeImage($file);

        try {
            return MediaAsset::create([...$attributes, 'disk' => 'public', 'alt_text' => $altText, 'uploaded_by' => $user?->id]);
        } catch (Throwable $exception) {
            $this->removeFile($attributes['path']);
            throw $exception;
        }
    }

    public function update(MediaAsset $media, ?UploadedFile $file, ?string $altText): MediaAsset
    {
        $newImage = $file ? $this->writeImage($file) : [];
        $oldPath = null;

        try {
            $updated = DB::transaction(function () use ($media, $newImage, $altText, &$oldPath): MediaAsset {
                $locked = MediaAsset::query()->lockForUpdate()->findOrFail($media->id);
                $this->assertManaged($locked);
                if ($newImage !== []) {
                    $oldPath = $locked->path;
                }
                $locked->update([...$newImage, 'alt_text' => $altText]);

                return $locked;
            });
        } catch (Throwable $exception) {
            if ($newImage !== []) {
                $this->removeFile($newImage['path']);
            }
            throw $exception;
        }

        if ($oldPath !== null) {
            $this->removeFile($oldPath);
        }

        return $updated;
    }

    /** @return array<string, int> */
    public function references(MediaAsset $media): array
    {
        $references = [];
        foreach (config('media.references') as $label => $reference) {
            $table = (new $reference['model'])->getTable();
            $count = DB::table($table)->where(function ($query) use ($reference, $media): void {
                foreach ($reference['columns'] as $column) {
                    $query->orWhere($column, $media->id);
                }
            })->count();
            if ($count > 0) {
                $references[$label] = $count;
            }
        }

        return $references;
    }

    public function delete(MediaAsset $media): void
    {
        $path = DB::transaction(function () use ($media): string {
            $locked = MediaAsset::query()->lockForUpdate()->findOrFail($media->id);
            $this->assertManaged($locked);
            $references = $this->references($locked);
            if ($references !== []) {
                throw ValidationException::withMessages(['media' => 'This image is in use by '.implode(', ', array_keys($references)).' (including archived records). Remove those references before deleting it.']);
            }
            $path = $locked->path;
            $locked->delete();

            return $path;
        });

        $this->removeFile($path);
    }

    public function assertManaged(MediaAsset $media): void
    {
        if ($media->disk !== 'public' || ! preg_match('~^uploads/[0-9]{4}/[0-9]{2}/[a-f0-9-]{36}\\.webp$~D', $media->path)) {
            throw ValidationException::withMessages(['media' => 'This file is not a managed website image.']);
        }
    }

    /** @return array{path: string, original_name: string, mime_type: string, size_bytes: int, width: int, height: int} */
    private function writeImage(UploadedFile $file): array
    {
        $image = @imagecreatefromstring($file->getContent());
        if ($image === false) {
            throw ValidationException::withMessages(['image' => 'The uploaded image could not be decoded.']);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        ob_start();
        try {
            imagepalettetotruecolor($image);
            imagesavealpha($image, true);
            if (! imagewebp($image, null, 85)) {
                throw new RuntimeException('Image encoding failed.');
            }
            $contents = ob_get_contents();
        } finally {
            ob_end_clean();
            imagedestroy($image);
        }

        if (! is_string($contents) || $contents === '') {
            throw new RuntimeException('Image encoding produced an empty file.');
        }
        $path = 'uploads/'.now()->format('Y/m').'/'.Str::uuid().'.webp';
        if (! Storage::disk('public')->put($path, $contents)) {
            throw new RuntimeException('The image could not be stored.');
        }

        return [
            'path' => $path,
            'original_name' => mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 255),
            'mime_type' => 'image/webp',
            'size_bytes' => strlen($contents),
            'width' => $width,
            'height' => $height,
        ];
    }

    private function removeFile(string $path): void
    {
        if (! Storage::disk('public')->delete($path)) {
            Log::warning('Unable to clean up a managed image file.', ['path' => $path]);
        }
    }
}
