<?php

namespace App\Services;

use App\Models\Idea;
use App\Services\Llm\Contracts\EmbeddingClient;
use App\Services\Llm\Contracts\EmbeddingRequest;
use App\Services\Llm\LlmException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * @wiki app/brain/services/IdeaIngestionService.md
 */
class IdeaIngestionService
{
    public function __construct(
        private readonly ?EmbeddingClient $embedding = null,
    ) {}

    public function ingest(IngestIdeaInput $input): Idea
    {
        $idea = new Idea();
        $idea->workspace_id    = $input->workspaceId;
        $idea->source          = $input->source;
        $idea->description     = $input->description;
        $idea->title           = $input->title;
        $idea->author_email    = $input->authorEmail;
        $idea->source_file_id  = $input->sourceFileId;
        $idea->save();

        // Embedding async-friendly: best-effort, falha aqui não quebra ingestão.
        $this->attachEmbedding($idea);

        return $idea;
    }

    /**
     * Tenta gerar e gravar o embedding da Idea. Erros do provider são logados
     * e silenciados — Idea sem embedding cai como singleton no pré-cluster.
     */
    public function attachEmbedding(Idea $idea): void
    {
        if (! $this->embedding) {
            return;
        }
        try {
            $text = trim((string) ($idea->title ?? '').' '.$idea->description);
            if ($text === '') {
                return;
            }
            $resp = $this->embedding->embed(new EmbeddingRequest(inputs: [$text]));
            $vector = $resp->vectors[0] ?? null;
            if ($vector === null) {
                return;
            }

            $driver = $idea->getConnection()->getDriverName();
            if ($driver === 'pgsql') {
                // pgvector aceita o cast no DB via texto: '[v1,v2,...]'
                $literal = '['.implode(',', array_map(fn ($v) => (string) (float) $v, $vector)).']';
                $idea->getConnection()
                    ->update(
                        'UPDATE ideas SET embedding = ?::vector WHERE id = ?',
                        [$literal, $idea->id]
                    );
            } else {
                $idea->setAttribute('embedding', $vector);
                $idea->save();
            }
        } catch (LlmException | Throwable $e) {
            Log::channel('clustering')->warning('ingestion.embedding.failed', [
                'idea_id' => $idea->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
