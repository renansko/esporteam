<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePermissionsRequest;
use App\Http\Resources\UserResource;
use App\Services\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(private AdminUserService $adminUsers) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['email', 'name', 'is_admin', 'is_owner']);
        $perPage = (int) $request->input('per_page', 25);

        $paginator = $this->adminUsers->listUsers($filters, $perPage);

        return $this->successResponse([
            'items' => UserResource::collection($paginator->items()),
            'meta'  => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ], __('messages.admin.users.listed'));
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->adminUsers->getUser($id);
        return $this->successResponse(new UserResource($user), __('messages.admin.users.retrieved'));
    }

    public function updatePermissions(UpdatePermissionsRequest $request, int $id): JsonResponse
    {
        $user = $this->adminUsers->updatePermissions(
            $request->user(),
            $id,
            (int) $request->validated()['permissions'],
        );

        return $this->successResponse(new UserResource($user), __('messages.admin.users.permissions_updated'));
    }
}
