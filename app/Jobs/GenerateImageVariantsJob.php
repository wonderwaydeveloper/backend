<?php

namespace App\Jobs;

use App\Models\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class GenerateImageVariantsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    public function __construct(public Media $media)
    {
    }

    public function handle(): void
    {
        if (!$this->media->isImage()) {
            Log::warning('GenerateImageVariantsJob called for non-image media', ['media_id' => $this->media->id]);
            return;
        }

        if (!Storage::disk('public')->exists($this->media->path)) {
            Log::error('Image file not found', ['media_id' => $this->media->id, 'path' => $this->media->path]);
            $this->fail(new \Exception('Image file not found'));
            return;
        }

        try {
            $manager = new ImageManager(new Driver());
            $imageContent = Storage::disk('public')->get($this->media->path);
            
            if (empty($imageContent)) {
                throw new \Exception('Image file is empty');
            }
            
            $variants = [];
            $sizes = config('media.image_variants');
            $quality = config('media.quality.image');

            foreach ($sizes as $name => $width) {
                try {
                    $image = $manager->read($imageContent);
                    
                    if ($image->width() > $width) {
                        $image->scale(width: $width);
                    }
                    
                    $pathInfo = pathinfo($this->media->path);
                    $variantPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . "_{$name}." . $pathInfo['extension'];
                    
                    $variantContent = $image->toWebp($quality)->toString();
                    Storage::disk('public')->put($variantPath, $variantContent);
                    
                    if (!Storage::disk('public')->exists($variantPath)) {
                        throw new \Exception("Failed to save {$name} variant");
                    }
                    
                    $variants[$name] = Storage::disk('public')->url($variantPath);
                    Log::info("Generated {$name} variant", ['media_id' => $this->media->id]);
                    
                } catch (\Exception $e) {
                    Log::error("Failed to generate {$name} variant", [
                        'media_id' => $this->media->id,
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
            }

            $variants['original'] = $this->media->url;
            $this->media->update(['image_variants' => $variants]);
            
            Log::info('Image variants generated successfully', [
                'media_id' => $this->media->id,
                'variants' => array_keys($variants)
            ]);
            
        } catch (\Exception $e) {
            Log::error('GenerateImageVariantsJob failed', [
                'media_id' => $this->media->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateImageVariantsJob failed permanently', [
            'media_id' => $this->media->id,
            'error' => $exception->getMessage()
        ]);
    }
}
