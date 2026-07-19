<?php

namespace App\Jobs;

use App\Services\ConversationMediaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessConversationMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 3;
    public array $backoff = [10, 60, 300];
    public function __construct(public readonly int $mediaId) { $this->onQueue('conversation-media'); }
    public function handle(ConversationMediaService $media): void { $media->processUpload($this->mediaId); }
}
