<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\IndexUserRequest;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Requests\Users\UpdateUserStatusRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * List users — paginated, searchable by name/email, filterable by role.
     */
    public function index(IndexUserRequest $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return UserResource::collection($users);
    }

    /**
     * Create a new user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return response()->json([
            'data'    => new UserResource($user),
            'message' => 'ok',
        ], 201);
    }

    /**
     * Update a user's name, email, role, and/or password.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user = $this->userService->update($user, $request->validated());

        return response()->json([
            'data'    => new UserResource($user),
            'message' => 'ok',
        ]);
    }

    /**
     * Activate or deactivate a user.
     */
    public function updateStatus(UpdateUserStatusRequest $request, User $user): JsonResponse
    {
        $user = $this->userService->setActive($user, $request->boolean('active'));

        return response()->json([
            'data'    => new UserResource($user),
            'message' => 'ok',
        ]);
    }
}
