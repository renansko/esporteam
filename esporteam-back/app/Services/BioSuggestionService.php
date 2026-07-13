<?php

namespace App\Services;

use App\Ai\Agents\BioAssistant;
use App\Enums\BioSuggestionStatus;
use App\Exceptions\BioSuggestionGenerationFailed;
use App\Exceptions\InsufficientBioContext;
use App\Exceptions\UnsafeBioSuggestion;
use App\Models\BioSuggestion;
use App\Models\Sport;
use App\Models\SportProfile;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @wiki app/brain/services/BioSuggestionService.md
 */
class BioSuggestionService
{
    public function __construct(
        private readonly BioAssistant $assistant,
        private readonly ProfileBioEmbeddingService $bioEmbeddings,
        private readonly AiOperationalAudit $audit,
    ) {}

    /**
     * @wiki app/brain/functions/BioSuggestionService.md#createForUser
     */
    public function createForUser(int $userId, ?string $instruction = null, ?string $idempotencyKey = null): BioSuggestion
    {
        $profile = $this->profileForUser($userId);
        $idempotencyKey = $this->normalizeIdempotencyKey($idempotencyKey);

        if ($idempotencyKey !== null) {
            $existing = $profile->bioSuggestions()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $this->replayed($existing);
            }
        }

        $instruction = $this->normalizeInstruction($instruction);

        if ($instruction !== null && $this->containsSensitiveData($instruction)) {
            throw new UnsafeBioSuggestion('unsafe_instruction');
        }

        $context = $this->safeContext($profile, $instruction);

        if ($context['sports'] === [] && $instruction === null) {
            throw new InsufficientBioContext;
        }

        try {
            $suggestion = BioSuggestion::query()->create([
                'sport_profile_id' => $profile->id,
                'status' => BioSuggestionStatus::Generating,
                'prompt_version' => (string) config('bio_assisted.prompt_version', 'bio_v1'),
                'context_fingerprint' => hash('sha256', json_encode($context, JSON_THROW_ON_ERROR)),
                'idempotency_key' => $idempotencyKey,
            ]);
        } catch (QueryException $e) {
            if ($idempotencyKey !== null) {
                $existing = $profile->bioSuggestions()->where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return $this->replayed($existing);
                }
            }

            throw $e;
        }
        $startedAt = hrtime(true);

        $response = null;

        try {
            $response = $this->assistant->prompt(
                $this->promptFor($context),
                provider: (string) config('bio_assisted.provider', 'openai'),
                model: (string) config('bio_assisted.model', 'gpt-4o-mini'),
                timeout: (int) config('bio_assisted.timeout_seconds', 30),
            );
            $output = $response->toArray();
            $this->validateOutput($output, $context);

            $suggestion->forceFill([
                'status' => BioSuggestionStatus::Generated,
                'generated_bio' => trim($output['bio']),
                'structured_output' => [
                    'bio' => trim($output['bio']),
                    'key_points' => array_values($output['key_points']),
                ],
                'provider' => $response->meta->provider,
                'model' => $response->meta->model,
                'tokens_input' => $response->usage->promptTokens,
                'tokens_output' => $response->usage->completionTokens,
                'metadata' => [
                    'finish_reason' => 'completed',
                ],
            ])->save();

            $this->audit->record('bio_generation', 'succeeded', $profile->id, "bio-suggestion:{$suggestion->id}:succeeded", [
                'provider' => $response->meta->provider,
                'model' => $response->meta->model,
                'prompt_version' => $suggestion->prompt_version,
                'tokens_input' => $response->usage->promptTokens,
                'tokens_output' => $response->usage->completionTokens,
                'duration_ms' => $this->durationMs($startedAt),
                'fallback_used' => false,
            ]);

            return $suggestion->fresh();
        } catch (UnsafeBioSuggestion $e) {
            $suggestion->forceFill([
                'status' => BioSuggestionStatus::Failed,
                'failure_code' => 'unsafe_output',
                'metadata' => ['outcome' => 'rejected'],
            ])->save();

            $this->audit->record('bio_generation', 'failed', $profile->id, "bio-suggestion:{$suggestion->id}:unsafe-output", [
                'provider' => $response?->meta->provider ?? config('bio_assisted.provider', 'openai'),
                'model' => $response?->meta->model ?? config('bio_assisted.model', 'gpt-4o-mini'),
                'prompt_version' => $suggestion->prompt_version,
                'tokens_input' => $response?->usage->promptTokens,
                'tokens_output' => $response?->usage->completionTokens,
                'duration_ms' => $this->durationMs($startedAt),
                'failure_category' => 'unsafe_output',
                'fallback_used' => false,
            ]);

            throw $e;
        } catch (Throwable $e) {
            $suggestion->forceFill([
                'status' => BioSuggestionStatus::Failed,
                'failure_code' => 'provider_unavailable',
                'metadata' => ['outcome' => 'provider_failure'],
            ])->save();

            Log::warning('bio_suggestion.failed', [
                'suggestion_id' => $suggestion->id,
                'exception' => $e::class,
            ]);

            $this->audit->record('bio_generation', 'failed', $profile->id, "bio-suggestion:{$suggestion->id}:provider-failure", [
                'provider' => config('bio_assisted.provider', 'openai'),
                'model' => config('bio_assisted.model', 'gpt-4o-mini'),
                'prompt_version' => $suggestion->prompt_version,
                'duration_ms' => $this->durationMs($startedAt),
                'failure_category' => 'provider_unavailable',
                'fallback_used' => false,
            ]);

            throw new BioSuggestionGenerationFailed;
        }
    }

    /**
     * @wiki app/brain/functions/BioSuggestionService.md#listForUser
     */
    public function listForUser(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        $profile = $this->profileForUser($userId);

        return $profile->bioSuggestions()
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * @wiki app/brain/functions/BioSuggestionService.md#acceptForUser
     */
    public function acceptForUser(int $userId, int $suggestionId): BioSuggestion
    {
        return DB::transaction(function () use ($userId, $suggestionId) {
            $profile = $this->profileForUser($userId);
            $suggestion = $profile->bioSuggestions()->lockForUpdate()->findOrFail($suggestionId);

            if ($suggestion->status === BioSuggestionStatus::Accepted) {
                return $suggestion;
            }

            $bio = trim((string) ($suggestion->structured_output['bio'] ?? ''));
            if ($suggestion->status !== BioSuggestionStatus::Generated
                || $bio === ''
                || $bio !== trim((string) $suggestion->generated_bio)
            ) {
                throw ValidationException::withMessages(['suggestion' => 'A sugestão não pode ser aceita.']);
            }

            $profile->forceFill([
                'bio' => $bio,
                'bio_assistant_onboarding_completed_at' => $profile->bio_assistant_onboarding_completed_at ?? now(),
            ])->save();
            $suggestion->forceFill(['status' => BioSuggestionStatus::Accepted])->save();

            $this->bioEmbeddings->synchronize($profile);

            return $suggestion->fresh();
        });
    }

    /**
     * @return array{display_name:string,sports:list<array<string,mixed>>,availability:list<array<string,mixed>>,instruction:?string}
     */
    private function safeContext(SportProfile $profile, ?string $instruction): array
    {
        return [
            'display_name' => $this->containsSensitiveData((string) $profile->display_name)
                ? 'Perfil Esportivo'
                : (string) $profile->display_name,
            'sports' => $profile->sports->map(fn ($practice) => [
                'modality' => $practice->sport?->name,
                'slug' => $practice->sport?->slug,
                'level' => $practice->level?->value ?? $practice->level,
                'goals' => array_values($practice->goals ?? []),
                'preferred_positions' => $practice->preferred_positions,
                'is_primary' => (bool) $practice->is_primary,
            ])->values()->all(),
            'availability' => $profile->availabilityWindows->map(fn ($window) => [
                'weekday' => (int) $window->weekday,
                'starts_at' => (string) $window->starts_at,
                'ends_at' => (string) $window->ends_at,
            ])->values()->all(),
            'instruction' => $instruction,
        ];
    }

    private function promptFor(array $context): string
    {
        return "Contexto autorizado do Perfil Esportivo (JSON):\n"
            .json_encode($context, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
            .'\n\nGere uma sugestão de bio fiel a esse contexto.';
    }

    private function validateOutput(mixed $output, array $context): void
    {
        $max = (int) config('bio_assisted.max_bio_chars', 320);
        if (! is_array($output)
            || ! is_string($output['bio'] ?? null)
            || trim($output['bio']) === ''
            || mb_strlen(trim($output['bio'])) > $max
            || ! is_array($output['key_points'] ?? null)
            || count($output['key_points']) > 3
            || collect($output['key_points'])->contains(fn ($point) => ! is_string($point) || mb_strlen($point) > 80)
        ) {
            throw new UnsafeBioSuggestion;
        }

        $text = trim($output['bio'].' '.implode(' ', $output['key_points']));
        $unsafePatterns = [
            '/(?:cref|certificad|diplom|campe[ãa]o|medalh|t[íi]tulo|premia|anos? de experi)/iu',
            '/(?:r\$|\bpre[cç]o\b|\bpagamento\b|\bcontato\b|\be-?mail\b|\btelefone\b)/iu',
            '/(?:\bprofessor(?:a)?\b|\btreinador(?:a)?\b|\binstrutor(?:a)?\b|\bcoach\b|\bpersonal\b|\borganizador(?:a)?\b)/iu',
            '/(?:\btenho\b.{0,20}\bexperi[êe]ncia\b|\bdou aulas?\b|\bofere[cç]o aulas?\b|\bministro aulas?\b|\bensino\b|\btrabalho como\b|\batendo alunos?\b|\bparticipo de\b|\bj[aá] competi\b|\bj[aá] joguei\b|\bsou especialista\b)/iu',
            '/(?:\bmoro\b|\bresido\b|\bsou de\b|\bendere[cç]o\b|\bna rua\b|\bno bairro\b|\bcep\b|\blatitude\b|\blongitude\b)/iu',
            '/\btenho\b\s+(?!vontade\b|interesse\b|objetivo\b|disponibilidade\b)/iu',
            '/[-+]?\d{1,3}\.\d{3,8}\s*[,;]\s*[-+]?\d{1,3}\.\d{3,8}/u',
            '/\d/u',
        ];

        foreach ($unsafePatterns as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                throw new UnsafeBioSuggestion;
            }
        }

        $authorizedModalities = collect($context['sports'])
            ->flatMap(fn (array $sport) => [$sport['modality'] ?? null, $sport['slug'] ?? null])
            ->filter()
            ->map(fn (string $modality) => mb_strtolower($modality))
            ->all();

        foreach (Sport::query()->get(['name', 'slug']) as $sport) {
            foreach ([(string) $sport->name, (string) $sport->slug] as $modality) {
                $modality = mb_strtolower($modality);
                if ($modality === '' || in_array($modality, $authorizedModalities, true)) {
                    continue;
                }

                $pattern = '/(?<!\pL)'.preg_quote($modality, '/').'(?!\pL)/iu';
                if (preg_match($pattern, $text) === 1) {
                    throw new UnsafeBioSuggestion;
                }
            }
        }

        $levelTerms = [
            'beginner' => ['beginner', 'iniciante'],
            'intermediate' => ['intermediate', 'intermediário', 'intermediario'],
            'advanced' => ['advanced', 'avançado', 'avancado'],
            'competitive' => ['competitive', 'competitivo'],
        ];
        $authorizedLevels = collect($context['sports'])
            ->pluck('level')
            ->filter()
            ->flatMap(fn (string $level) => $levelTerms[$level] ?? [$level])
            ->map(fn (string $level) => mb_strtolower($level))
            ->all();
        foreach ($levelTerms as $terms) {
            foreach ($terms as $level) {
                if (! in_array($level, $authorizedLevels, true)
                    && preg_match('/(?<!\pL)'.preg_quote($level, '/').'(?!\pL)/iu', $text) === 1
                ) {
                    throw new UnsafeBioSuggestion;
                }
            }
        }

        $authorizedTerms = array_merge($authorizedModalities, [
            'esporte', 'esportes', 'fazer', 'conhecer', 'jogar', 'treinar', 'aprender', 'novas', 'pessoas', 'amizades',
        ]);
        preg_match_all('/\b(?:pratico|jogo|treino|fa[cç]o|gosto de)\s+([\p{L}-]+)/iu', $text, $claims);
        foreach ($claims[1] ?? [] as $term) {
            if (! in_array(mb_strtolower($term), $authorizedTerms, true)) {
                throw new UnsafeBioSuggestion;
            }
        }
    }

    private function containsSensitiveData(string $value): bool
    {
        return preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/iu', $value) === 1
            || preg_match('/\+?\d[\d\s().-]{7,}\d/u', $value) === 1
            || preg_match('/[-+]?\d{1,3}\.\d{3,8}\s*[,;]\s*[-+]?\d{1,3}\.\d{3,8}/u', $value) === 1
            || preg_match('/\b(?:user|profile|sport_profile|report|block|connection)_?id\b\s*[:=]?\s*\d+/iu', $value) === 1;
    }

    private function profileForUser(int $userId): SportProfile
    {
        return SportProfile::query()
            ->with(['sports.sport', 'availabilityWindows'])
            ->where('user_id', $userId)
            ->firstOrFail();
    }

    private function normalizeInstruction(?string $instruction): ?string
    {
        $instruction = trim((string) $instruction);

        return $instruction === '' ? null : $instruction;
    }

    private function normalizeIdempotencyKey(?string $idempotencyKey): ?string
    {
        $idempotencyKey = trim((string) $idempotencyKey);

        return $idempotencyKey === '' ? null : $idempotencyKey;
    }

    private function replayed(BioSuggestion $suggestion): BioSuggestion
    {
        $suggestion->wasReplayed = true;

        return $suggestion;
    }

    private function durationMs(int $startedAt): int
    {
        return (int) floor((hrtime(true) - $startedAt) / 1_000_000);
    }
}
