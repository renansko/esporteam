<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckWorkspaceActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->is_esporteam_admin ?? false) === true) {
            return $next($request);
        }

        $workspaceId = null;
        $routeParam = $request->route('workspace');

        if ($routeParam instanceof Workspace) {
            $workspaceId = $routeParam->id;
            $workspace = $routeParam;
        } elseif (is_numeric($routeParam)) {
            $workspaceId = (int) $routeParam;
            $workspace = Workspace::find($workspaceId);
        } elseif ($user && !empty($user->workspace_id)) {
            $workspaceId = (int) $user->workspace_id;
            $workspace = Workspace::find($workspaceId);
        } else {
            return $next($request);
        }

        if ($workspace && !$workspace->is_active) {
            return response()->json([
                'success' => false,
                'message' => __('messages.workspace.deactivated'),
                'error_code' => 'workspace_deactivated',
            ], 403);
        }

        return $next($request);
    }
}
