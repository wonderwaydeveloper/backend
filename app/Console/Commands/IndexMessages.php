<?php

namespace App\Console\Commands;

use App\Models\Message;
use Illuminate\Console\Command;

class IndexMessages extends Command
{
    protected $signature = 'messages:index';
    protected $description = 'Index all text messages in Meilisearch';

    public function handle()
    {
        $this->info('Starting to index messages...');

        $count = Message::where('message_type', 'text')
            ->whereNotNull('content')
            ->count();

        $this->info("Found {$count} text messages to index");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        Message::where('message_type', 'text')
            ->whereNotNull('content')
            ->chunk(100, function ($messages) use ($bar) {
                $messages->searchable();
                $bar->advance($messages->count());
            });

        $bar->finish();
        $this->newLine();
        $this->info('Messages indexed successfully!');

        return 0;
    }
}
