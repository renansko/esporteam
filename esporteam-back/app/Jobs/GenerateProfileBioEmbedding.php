<?php

namespace App\Jobs;

use App\Services\ProfileBioEmbeddingGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateProfileBioEmbedding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(
        public int $profileId,
        public string $sourceHash,
    ) {
        $this->onQueue('embeddings');
    }

    public function handle(ProfileBioEmbeddingGenerationService $embeddings): void
    {
        $embeddings->generate($this->profileId, $this->sourceHash, max(1, $this->attempts()), $this->tries);
    }
}
