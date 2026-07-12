<?php

namespace App\Services;

use App\Jobs\GenerateProfileBioEmbedding;
use App\Models\ProfileBioEmbedding;
use App\Models\SportProfile;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the single embedding record aligned with the current public bio.
 *
 * @wiki app/brain/services/ProfileBioEmbeddingService.md
 */
class ProfileBioEmbeddingService
{
    /**
     * Invalidates the previous vector and queues the current bio when needed.
     * An empty bio has no embedding and never queues provider work.
     */
    public function synchronize(SportProfile $profile): void
    {
        $bio = trim((string) $profile->bio);

        if ($bio === '') {
            ProfileBioEmbedding::query()
                ->where('sport_profile_id', $profile->id)
                ->delete();

            return;
        }

        $sourceHash = hash('sha256', (string) $profile->bio);
        $record = ProfileBioEmbedding::query()
            ->where('sport_profile_id', $profile->id)
            ->first();

        if ($record?->source_hash === $sourceHash
            && $record->status === 'completed'
            && $record->embedding !== null
        ) {
            return;
        }

        ProfileBioEmbedding::query()->updateOrCreate(
            ['sport_profile_id' => $profile->id],
            [
                'status' => 'pending',
                'source_hash' => $sourceHash,
                'model' => null,
                'embedded_at' => null,
                'failure_code' => null,
                'metadata' => null,
                'embedding' => null,
            ],
        );

        DB::afterCommit(fn () => GenerateProfileBioEmbedding::dispatch($profile->id, $sourceHash));
    }
}
