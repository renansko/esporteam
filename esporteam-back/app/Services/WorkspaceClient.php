<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WorkspaceClient
{
    public function __construct(
        private readonly string $baseUrl,
    ) {}

    /**
     * Lista workspaces do usuário pelo JWT. Vazio se o serviço falhar.
     */
    public function listForToken(string $token): array
    {
        if ($token === '') {
            return [];
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(3)
            ->get($this->baseUrl.'/api/workspaces');

        if (!$response->successful()) {
            return [];
        }

        return $response->json('data') ?? [];
    }
}
