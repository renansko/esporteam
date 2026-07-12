<?php

namespace App\Console\Commands;

use App\Models\SportProfile;
use App\Services\ProfileBioEmbeddingService;
use Illuminate\Console\Command;

class BackfillProfileBioEmbeddingsCommand extends Command
{
    protected $signature = 'profile-bio-embeddings:backfill {--batch=200}';

    protected $description = 'Reagenda embeddings ausentes, obsoletos ou falhos das bios atuais.';

    public function handle(ProfileBioEmbeddingService $embeddings): int
    {
        $batchSize = max(1, (int) $this->option('batch'));
        $count = 0;

        SportProfile::query()
            ->whereNotNull('bio')
            ->where('bio', '!=', '')
            ->orderBy('id')
            ->chunkById($batchSize, function ($profiles) use ($embeddings, &$count): void {
                foreach ($profiles as $profile) {
                    $record = $profile->bioEmbedding;
                    $sourceHash = hash('sha256', (string) $profile->bio);

                    if ($record?->source_hash === $sourceHash
                        && $record->status === 'completed'
                        && $record->embedding !== null
                    ) {
                        continue;
                    }

                    $embeddings->synchronize($profile);
                    $count++;
                }
            });

        $this->info("Backfill: {$count} perfil(is) reagendado(s).");

        return self::SUCCESS;
    }
}
