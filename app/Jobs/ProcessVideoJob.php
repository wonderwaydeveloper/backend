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
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\TimeCode;

class ProcessVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 3600;
    public $maxExceptions = 3;

    public function __construct(public Media $media)
    {
    }

    public function handle(): void
    {
        $ffmpegPath = env('FFMPEG_BINARIES', '/usr/bin/ffmpeg');
        if (!file_exists($ffmpegPath)) {
            Log::error('FFMpeg binary not found', ['media_id' => $this->media->id, 'path' => $ffmpegPath]);
            $this->media->update(['encoding_status' => 'failed', 'processing_progress' => 0]);
            $this->fail(new \Exception('FFMpeg binary not found'));
            return;
        }

        if (!Storage::disk('public')->exists($this->media->path)) {
            Log::error('Video file not found', ['media_id' => $this->media->id, 'path' => $this->media->path]);
            $this->media->update(['encoding_status' => 'failed', 'processing_progress' => 0]);
            $this->fail(new \Exception('Video file not found'));
            return;
        }

        $this->media->update(['encoding_status' => 'processing', 'processing_progress' => 0]);

        try {
            $ffmpeg = FFMpeg::create([
                'ffmpeg.binaries'  => env('FFMPEG_BINARIES', '/usr/bin/ffmpeg'),
                'ffprobe.binaries' => env('FFPROBE_BINARIES', '/usr/bin/ffprobe'),
                'timeout'          => 3600,
                'ffmpeg.threads'   => 12,
            ]);
            
            $videoPath = Storage::disk('public')->path($this->media->path);
            $video = $ffmpeg->open($videoPath);
            
            $duration = (int) $ffmpeg->getFFProbe()->format($videoPath)->get('duration');
            $maxDuration = config('media.video_dimensions.max_duration');
            
            if ($duration > $maxDuration) {
                Log::warning('Video duration exceeds limit', [
                    'media_id' => $this->media->id,
                    'duration' => $duration,
                    'max_duration' => $maxDuration
                ]);
                $this->media->update(['encoding_status' => 'failed', 'processing_progress' => 0]);
                throw new \Exception("Video duration ({$duration}s) exceeds maximum allowed duration of {$maxDuration}s");
            }
            
            $qualities = [];
            $resolutions = config('media.video_qualities');
            $totalQualities = count($resolutions);
            $currentQuality = 0;

            foreach ($resolutions as $quality => $config) {
                $outputPath = str_replace('.', "_{$quality}.", $this->media->path);
                $format = (new X264())->setKiloBitrate($config['bitrate'])->setAudioCodec('aac')->setAudioKiloBitrate(128);
                
                $video->filters()->resize(new Dimension($config['width'], $config['height']));
                $video->save($format, Storage::disk('public')->path($outputPath));
                
                if (!Storage::disk('public')->exists($outputPath)) {
                    throw new \Exception("Failed to create {$quality} quality video");
                }
                
                $qualities[$quality] = Storage::disk('public')->url($outputPath);
                $currentQuality++;
                $progress = (int)(($currentQuality / $totalQualities) * 90);
                $this->media->update(['processing_progress' => $progress]);
                
                Log::info("Generated {$quality} quality", ['media_id' => $this->media->id]);
            }

            $thumbnailPath = str_replace(pathinfo($this->media->path, PATHINFO_EXTENSION), 'jpg', $this->media->path);
            $video->frame(TimeCode::fromSeconds(1))->save(Storage::disk('public')->path($thumbnailPath));
            
            if (!Storage::disk('public')->exists($thumbnailPath)) {
                Log::warning('Failed to generate thumbnail', ['media_id' => $this->media->id]);
            }

            $this->media->update([
                'encoding_status' => 'completed',
                'video_qualities' => $qualities,
                'thumbnail_url' => Storage::disk('public')->url($thumbnailPath),
                'processing_progress' => 100,
                'duration' => $duration,
            ]);
            
            Log::info('Video processing completed', ['media_id' => $this->media->id]);
            
        } catch (\Exception $e) {
            Log::error('Video processing failed', [
                'media_id' => $this->media->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->media->update(['encoding_status' => 'failed', 'processing_progress' => 0]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessVideoJob failed permanently', [
            'media_id' => $this->media->id,
            'error' => $exception->getMessage()
        ]);
        
        $this->media->update([
            'encoding_status' => 'failed',
            'processing_progress' => 0
        ]);
    }
}
