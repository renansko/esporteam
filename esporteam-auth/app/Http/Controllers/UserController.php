<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\UpdateMeRequest;
use App\Http\Requests\Auth\UpdatePermissionsRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function updatePermissions(UpdatePermissionsRequest $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $authUser = request()->user();

        if ((int) $authUser->id !== $id) {
            return $this->errorResponse('You can only update your own permissions.', statusCode: 403);
        }

        $user = $this->userService->updatePermissions($user, $request->validated()['permissions']);
        return $this->successResponse(new UserResource($user), 'Permissions updated successfully');
    }

    /**
     * Self-service profile update (nome e email).
     */
    public function updateMe(UpdateMeRequest $request): JsonResponse
    {
        $user = $this->userService->updateMe($request->user(), $request->validated());
        return $this->successResponse(new UserResource($user), 'Profile updated successfully');
    }

    /**
     * Self-service account deletion. Soft delete + anonimização. A confirmação
     * do usuário é responsabilidade do frontend (dupla confirmação) conforme
     * requisito Apple 5.1.1(v).
     */
    public function deleteMe(Request $request): JsonResponse
    {
        $this->userService->softDelete($request->user());

        return $this->successResponse(null, 'Conta excluída com sucesso.', 200);
    }
}
