<?php

namespace App\Jobs;

use App\Models\ProfileBioEmbedding;
use App\Models\SportProfile;
use App\Services\Llm\Contracts\EmbeddingClient;
use App\Services\Llm\Contracts\EmbeddingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

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

    public function handle(EmbeddingClient $embedding): void
    {
        $profile = SportProfile::query()->find($this->profileId);
        if (! $profile || ! is_string($profile->bio) || hash('sha256', $profile->bio) !== $this->sourceHash) {
            return;
        }

        $record = ProfileBioEmbedding::query()->firstOrCreate(
            ['sport_profile_id' => $profile->id],
            ['source_hash' => $this->sourceHash, 'status' => 'pending'],
        );

        if (! $record || $record->source_hash !== $this->sourceHash || $record->status === 'completed') {
            return;
        }

        try {
            // The accepted public bio is the only provider input.
            $response = $embedding->embed(new EmbeddingRequest(inputs: [$profile->bio]));
            $vector = $response->vectors[0] ?? null;
            if (! $this->validVector($vector)) {
                $this->markFailed($record, 'invalid_vector');

                return;
            }

            if (hash('sha256', (string) $profile->fresh()->bio) !== $this->sourceHash) {
                return;
            }

            $values = [
                'status' => 'completed',
                'model' => $response->modelUsed,
                'embedded_at' => now(),
                'failure_code' => null,
                'metadata' => ['tokens_used' => $response->tokensUsed],
                'updated_at' => now(),
            ];

            DB::transaction(function () use ($record, $profile, $values, $vector): void {
                $query = ProfileBioEmbedding::query()
                    ->whereKey($record->id)
                    ->where('source_hash', $this->sourceHash)
                    ->whereHas('profile', fn ($profiles) => $profiles->where('bio', $profile->bio));

                if (DB::connection()->getDriverName() === 'pgsql') {
                    if ($query->update($values) === 0) {
                        return;
                    }

                    $literal = '['.implode(',', array_map(static fn (float $value) => (string) $value, $vector)).']';
                    DB::update('UPDATE profile_bio_embeddings SET embedding = ?::vector WHERE id = ? AND source_hash = ?', [$literal, $record->id, $this->sourceHash]);

                    return;
                }

                $query->update($values + ['embedding' => $vector]);
            });
        } catch (Throwable $exception) {
            $this->markFailed($record, 'provider_unavailable');
            Log::warning('profile_bio_embedding.failed', [
                'sport_profile_id' => $this->profileId,
                'exception' => $exception::class,
            ]);

            throw $exception;
        }
    }

    private function markFailed(ProfileBioEmbedding $record, string $failureCode): void
    {
        ProfileBioEmbedding::query()
            ->whereKey($record->id)
            ->where('source_hash', $this->sourceHash)
            ->update([
                'status' => 'failed',
                'failure_code' => $failureCode,
                'metadata' => ['outcome' => 'failed'],
            ]);
    }

    private function validVector(mixed $vector): bool
    {
        return is_array($vector)
            && count($vector) === 1536
            && collect($vector)->every(static fn (mixed $value) => is_numeric($value) && is_finite((float) $value));
    }
}
