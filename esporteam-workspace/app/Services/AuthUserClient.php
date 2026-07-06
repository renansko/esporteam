<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthUserClient
{
    /**
     * Lookup user info (name, email, profile) in esporteam-auth by ids.
     *
     * @param  array<int>  $ids
     * @return array<int, array{id:int,name:string,email:string,profile:string}>  keyed by user id
     */
    public function lookup(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if (empty($ids)) {
            return [];
        }

        try {
            $url = rtrim((string) config('services.auth.url'), '/') . '/api/service/users/bulk-lookup';

            $response = Http::withHeaders([
                'X-Service-Token' => (string) config('services.auth.service_token'),
                'Accept'          => 'application/json',
            ])->post($url, [
                'ids' => $ids,
            ]);

            if (!$response->successful()) {
                Log::error('AuthUserClient: non-successful response from esporteam-auth', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return [];
            }

            $data = $response->json('data') ?? [];

            $result = [];
            foreach ($data as $user) {
                if (!isset($user['id'])) {
                    continue;
                }
                $id = (int) $user['id'];
                $result[$id] = [
                    'id'      => $id,
                    'name'    => (string) ($user['name'] ?? ''),
                    'email'   => (string) ($user['email'] ?? ''),
                    'profile' => (string) ($user['profile'] ?? 'user'),
                ];
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('AuthUserClient: failed to lookup users', [
                'exception' => $e->getMessage(),
                'ids'       => $ids,
            ]);

            return [];
        }
    }
}
