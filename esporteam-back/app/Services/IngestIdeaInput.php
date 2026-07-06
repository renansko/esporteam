<?php

namespace App\Services;

use App\Enums\IdeaSource;

final class IngestIdeaInput
{
    public function __construct(
        public readonly int $workspaceId,
        public readonly IdeaSource $source,
        public readonly string $description,
        public readonly ?string $title = null,
        public readonly ?string $authorEmail = null,
        public readonly ?int $sourceFileId = null,
    ) {}
}
