<?php

namespace App\Jobs;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\Log;

class ProcessMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function handle(): void
    {
        try {
            if ($this->message->media_path) {
                $this->processMedia();
            }

            if ($this->message->content) {
                $this->moderateContent();
            }
        } catch (\Exception $e) {
            Log::error('Failed to process message', [
                'message_id' => $this->message->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function processMedia(): void
    {
        Log::info('Processing media for message', ['message_id' => $this->message->id]);
    }

    private function moderateContent(): void
    {
        Log::info('Moderating content for message', ['message_id' => $this->message->id]);
    }
}
