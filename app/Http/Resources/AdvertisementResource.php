<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvertisementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'placement' => $this->placement,
            'media_id' => $this->media_id,
            'image_url' => $this->whenLoaded('media', fn () => $this->media?->url),
            'link_url' => $this->link_url,
            'is_active' => $this->is_active,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'sort_order' => $this->sort_order,
        ];
    }
}
