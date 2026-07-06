<?php

namespace App\Http\Controllers;

use App\Services\WorkspaceClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request, WorkspaceClient $workspaceClient): JsonResponse
    {
        $user        = $request->user();
        $workspaceId = $request->workspace_id();

        $workspace = null;
        if ($workspaceId !== null) {
            $workspaces = $workspaceClient->listForToken((string) $request->bearerToken());
            foreach ($workspaces as $w) {
                if (($w['id'] ?? null) == $workspaceId) {
                    $workspace = $w;
                    break;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'user' => [
                    'id'    => $user->id    ?? null,
                    'email' => $user->email ?? null,
                    'name'  => $user->name  ?? null,
                    'profile' => $user->profile ?? 'user',
                    'permissions' => $user->permissions ?? 0,
                ],
                'workspace' => $workspace,
            ],
        ]);
    }
}
