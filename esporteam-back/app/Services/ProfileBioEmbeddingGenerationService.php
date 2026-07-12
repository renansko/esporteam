<?php

namespace App\Services;

use App\Models\ProfileBioEmbedding;
use App\Models\SportProfile;
use App\Services\Llm\Contracts\EmbeddingClient;
use App\Services\Llm\Contracts\EmbeddingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Generates and audits the vector for the current accepted profile bio.
 *
 * @wiki app/brain/services/ProfileBioEmbeddingGenerationService.md
 */
class ProfileBioEmbeddingGenerationService
{
    public function __construct(
        private readonly EmbeddingClient $embedding,
        private readonly AiOperationalAudit $audit,
    ) {}

    /** @wiki app/brain/functions/ProfileBioEmbeddingGenerationService.md#generate */
    public function generate(int $profileId, string $sourceHash, int $attempt, int $maxAttempts): void
    {
        $profile = SportProfile::query()->find($profileId);
        if (! $profile || ! is_string($profile->bio) || hash('sha256', $profile->bio) !== $sourceHash) {
            return;
        }

        $record = ProfileBioEmbedding::query()->firstOrCreate(
            ['sport_profile_id' => $profile->id],
            ['source_hash' => $sourceHash, 'status' => 'pending'],
        );
        if ($record->source_hash !== $sourceHash || $record->status === 'completed') {
            return;
        }

        $startedAt = hrtime(true);
        try {
            $response = $this->embedding->embed(new EmbeddingRequest(inputs: [$profile->bio]));
            $vector = $response->vectors[0] ?? null;
            if (! $this->validVector($vector)) {
                $this->markFailed($record, $sourceHash, 'invalid_vector');
                $this->recordFailure($record, $profileId, $sourceHash, 'invalid_vector', $startedAt, $attempt, false);

                return;
            }
            if (hash('sha256', (string) $profile->fresh()->bio) !== $sourceHash) {
                return;
            }

            $values = [
                'status' => 'completed', 'model' => $response->modelUsed, 'embedded_at' => now(),
                'failure_code' => null, 'metadata' => ['tokens_used' => $response->tokensUsed], 'updated_at' => now(),
            ];
            DB::transaction(function () use ($record, $profile, $sourceHash, $values, $vector): void {
                $query = ProfileBioEmbedding::query()->whereKey($record->id)->where('source_hash', $sourceHash)
                    ->whereHas('profile', fn ($profiles) => $profiles->where('bio', $profile->bio));
                if (DB::connection()->getDriverName() === 'pgsql') {
                    if ($query->update($values) === 0) {
                        return;
                    }
                    $literal = '['.implode(',', array_map(static fn (float $value) => (string) $value, $vector)).']';
                    DB::update('UPDATE profile_bio_embeddings SET embedding = ?::vector WHERE id = ? AND source_hash = ?', [$literal, $record->id, $sourceHash]);

                    return;
                }
                $query->update($values + ['embedding' => $vector]);
            });

            if ($record->fresh()?->status === 'completed') {
                $this->audit->record('bio_embedding', 'succeeded', $profileId, "bio-embedding:{$record->id}:{$sourceHash}:succeeded", [
                    'provider' => config('llm.default_for_embeddings', 'openai'), 'model' => $response->modelUsed,
                    'tokens_total' => $response->tokensUsed, 'duration_ms' => $this->durationMs($startedAt),
                    'retry_attempt' => $attempt, 'fallback_used' => false,
                ]);
            }
        } catch (Throwable $exception) {
            $this->markFailed($record, $sourceHash, 'provider_unavailable');
            $this->recordFailure($record, $profileId, $sourceHash, 'provider_unavailable', $startedAt, $attempt, $attempt < $maxAttempts);
            Log::warning('profile_bio_embedding.failed', ['sport_profile_id' => $profileId, 'exception' => $exception::class]);
            throw $exception;
        }
    }

    private function markFailed(ProfileBioEmbedding $record, string $sourceHash, string $failureCode): void
    {
        ProfileBioEmbedding::query()->whereKey($record->id)->where('source_hash', $sourceHash)->update([
            'status' => 'failed', 'failure_code' => $failureCode, 'metadata' => ['outcome' => 'failed'],
        ]);
    }

    private function validVector(mixed $vector): bool
    {
        return is_array($vector) && count($vector) === 1536
            && collect($vector)->every(static fn (mixed $value) => is_numeric($value) && is_finite((float) $value));
    }

    private function recordFailure(ProfileBioEmbedding $record, int $profileId, string $sourceHash, string $category, int $startedAt, int $attempt, bool $retryScheduled): void
    {
        $this->audit->record('bio_embedding', 'failed', $profileId, "bio-embedding:{$record->id}:{$sourceHash}:failed:{$category}:{$attempt}", [
            'provider' => config('llm.default_for_embeddings', 'openai'),
            'model' => config('llm.providers.openai.embedding_model', 'text-embedding-3-small'), 'tokens_total' => 0,
            'duration_ms' => $this->durationMs($startedAt), 'failure_category' => $category,
            'retry_attempt' => $attempt, 'retry_scheduled' => $retryScheduled, 'fallback_used' => false,
        ]);
    }

    private function durationMs(int $startedAt): int
    {
        return (int) floor((hrtime(true) - $startedAt) / 1_000_000);
    }
}
