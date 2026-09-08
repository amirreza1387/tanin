<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AdvertisementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'placement' => $this->placement,
            'media_id' => $this->media_id,
            'image_url' => $this->whenLoaded('media', function (): ?string {
                if (! $this->media) {
                    return null;
                }

                // Public uploads are served by this application. A relative URL
                // keeps them working when APP_URL is unset or points to localhost.
                if ($this->media->disk === 'public') {
                    return '/storage/'.ltrim($this->media->path, '/');
                }

                return Storage::disk($this->media->disk)->url($this->media->path);
            }),
            'link_url' => $this->link_url,
            'is_active' => $this->is_active,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'sort_order' => $this->sort_order,
        ];
    }
}
