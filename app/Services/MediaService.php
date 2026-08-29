<?php

namespace App\Services;

use App\Enums\MediaType;
use App\Jobs\GenerateMediaVariants;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaService
{
    public function upload(User $user, UploadedFile $file): Media
    {
        $type = str_starts_with((string) $file->getMimeType(), 'video/')
            ? MediaType::VIDEO
            : MediaType::IMAGE;
        $path = $file->store('media/'.now()->format('Y/m/d'), 'public');
        [$width, $height] = $type === MediaType::IMAGE
            ? array_pad(getimagesize($file->getRealPath()) ?: [], 2, null)
            : [null, null];

        $media = Media::create([
            'uploaded_by' => $user->id,
            'disk' => 'public',
            'path' => $path,
            'type' => $type,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
        ]);

        if ($type === MediaType::IMAGE) {
            GenerateMediaVariants::dispatch($media);
        }

        return $media;
    }

    public function url(Media $media): string
    {
        return Storage::disk($media->disk)->url($media->path);
    }
}
