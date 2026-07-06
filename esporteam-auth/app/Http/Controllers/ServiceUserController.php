<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceUserController extends Controller
{
    public function __construct(private AdminUserService $adminUserService) {}

    public function findByEmail(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->errorResponse('User not found', null, 404);
        }

        return $this->successResponse(new UserResource($user), 'User found');
    }

    public function bulkLookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids'   => 'required|array|min:1|max:100',
            'ids.*' => 'integer|min:1',
        ]);

        $users = $this->adminUserService->findByIds($validated['ids']);

        return $this->successResponse($users, 'Users retrieved successfully');
    }

    public function grantPermissions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id'     => 'required|integer|min:1',
            'permissions' => 'required|integer|min:0',
        ]);

        try {
            $user = $this->adminUserService->grantPermissionsViaService(
                $validated['user_id'],
                $validated['permissions'],
            );
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        }

        return $this->successResponse(new UserResource($user), 'Permissions granted successfully');
    }
}
