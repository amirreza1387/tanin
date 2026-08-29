<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $mediaUrl = fn (?string $path): ?string => $path === null
            ? null
            : '/storage/'.ltrim($path, '/');
        $variants = collect($this->variants ?? [])
            ->map($mediaUrl)
            ->all();

        return [
            'id' => $this->id,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'url' => $mediaUrl($this->path),
            'variants' => $variants,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
