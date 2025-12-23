<?php

namespace App\Actions;

use App\DTOs\PostDTO;
use App\Events\PostPublished;
use App\Jobs\ProcessPostJob;
use App\Models\Post;
use App\Services\SpamDetectionService;
use App\Services\VideoUploadService;
use Illuminate\Http\UploadedFile;

class CreatePostAction
{
    public function __construct(
        private SpamDetectionService $spamDetectionService,
        private VideoUploadService $videoUploadService
    ) {}

    public function execute(PostDTO $postDTO, ?UploadedFile $image = null, ?UploadedFile $video = null): Post
    {
        $postData = $postDTO->toArray();
        $postData['content'] = $this->sanitizeContent($postData['content']);

        if ($image) {
            $postData['image'] = $image->store('posts', 'public');
        }

        if ($video) {
            $postData['video'] = 'processing';
        }

        $post = Post::create($postData);

        $this->processPostAfterCreation($post, $postDTO->isDraft, $video);

        return $post->load('user:id,name,username,avatar', 'hashtags');
    }

    private function processPostAfterCreation(Post $post, bool $isDraft, ?UploadedFile $video): void
    {
        if ($video) {
            $this->videoUploadService->uploadVideo($video, $post);
        }

        $post->syncHashtags();
        $post->processMentions($post->content);

        if (!$isDraft) {
            $this->handleSpamDetection($post);
            dispatch(new ProcessPostJob($post))->onQueue('high');
            broadcast(new PostPublished($post->load('user:id,name,username,avatar')));
        }
    }

    private function handleSpamDetection(Post $post): void
    {
        $spamResult = $this->spamDetectionService->checkPost($post);

        if ($spamResult['is_spam']) {
            $post->delete();
            throw new \App\Exceptions\BusinessLogicException(
                'پست شما به دلیل مشکوک بودن تأیید نشد',
                'SPAM_DETECTED'
            );
        }
    }

    private function sanitizeContent(string $content): string
    {
        $content = strip_tags($content);
        $content = str_replace(chr(0), '', $content);
        $content = preg_replace('/\s+/', ' ', $content);
        return trim($content);
    }
}