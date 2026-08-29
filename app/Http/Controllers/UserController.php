<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('manage', User::class);
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        return ApiResponse::paginated(UserResource::class, User::query()->latest()->paginate($perPage));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('manage', User::class);
        $user->update($request->validated());

        return ApiResponse::success(new UserResource($user->fresh()));
    }
}
