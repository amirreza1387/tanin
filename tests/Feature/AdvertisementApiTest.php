<?php

use App\Enums\MediaType;
use App\Models\Advertisement;
use App\Models\Media;
use App\Models\User;
use Illuminate\Support\Carbon;

it('shows only currently active advertisements on the public endpoint', function (): void {
    $now = Carbon::now();

    $visible = Advertisement::create(['title' => 'Visible', 'placement' => 'sidebar', 'sort_order' => 1]);
    $inactive = Advertisement::create(['title' => 'Inactive', 'placement' => 'horizontal', 'is_active' => false]);
    $future = Advertisement::create(['title' => 'Future', 'placement' => 'footer', 'starts_at' => $now->copy()->addHour()]);
    $expired = Advertisement::create(['title' => 'Expired', 'placement' => 'footer', 'ends_at' => $now->copy()->subHour()]);

    $this->getJson('/api/v1/advertisements')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $visible->id)
        ->assertJsonMissing(['id' => $inactive->id])
        ->assertJsonMissing(['id' => $future->id])
        ->assertJsonMissing(['id' => $expired->id]);

    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/advertisements')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $visible->id);
});

it('allows only admins to view all advertisements in management and returns media urls', function (): void {
    $admin = User::factory()->admin()->create();
    $media = Media::create([
        'uploaded_by' => $admin->id,
        'disk' => 'public',
        'path' => 'media/ads/banner.jpg',
        'type' => MediaType::IMAGE,
        'original_name' => 'banner.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 100,
    ]);
    $ad = Advertisement::create([
        'title' => 'Inactive management ad',
        'placement' => 'sidebar',
        'media_id' => $media->id,
        'is_active' => false,
    ]);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/management/advertisements')
        ->assertOk()
        ->assertJsonPath('data.0.id', $ad->id)
        ->assertJsonPath('data.0.image_url', '/storage/media/ads/banner.jpg');

    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/management/advertisements')
        ->assertForbidden();
});
