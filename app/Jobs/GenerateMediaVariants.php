<?php

namespace App\Jobs;

use App\Models\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateMediaVariants implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly Media $media) {}

    public function handle(): void
    {
        if (! function_exists('imagecreatefromstring')) {
            return;
        }

        $disk = Storage::disk($this->media->disk);
        $source = @imagecreatefromstring($disk->get($this->media->path));
        if ($source === false) {
            return;
        }

        $variants = [];
        foreach (['thumbnail' => 320, 'medium' => 800, 'large' => 1440] as $name => $maxWidth) {
            $width = imagesx($source);
            $height = imagesy($source);
            $targetWidth = min($width, $maxWidth);
            $targetHeight = (int) round($height * ($targetWidth / $width));
            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
            ob_start();
            imagejpeg($canvas, null, 85);
            $contents = ob_get_clean();
            imagedestroy($canvas);

            $variantPath = preg_replace('/(\.[^.]+)$/', '-'.$name.'.jpg', $this->media->path) ?: $this->media->path.'-'.$name.'.jpg';
            $disk->put($variantPath, $contents);
            $variants[$name] = $variantPath;
        }

        imagedestroy($source);
        $this->media->update(['variants' => $variants]);
    }
}
