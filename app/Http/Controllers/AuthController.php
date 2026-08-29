<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $key = 'register:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return ApiResponse::error(__('messages.register_rate_limited'), [], 429);
        }
        RateLimiter::hit($key, 60);

        $user = User::create($request->validated() + ['role' => 'user']);
        $token = $user->createToken('api')->plainTextToken;

        return ApiResponse::success([
            'user' => (new UserResource($user))->resolve(),
            'token' => $token,
            'token_type' => 'Bearer',
        ], [], 201);
    }

    public function login(LoginRequest $request)
    {
        $key = 'login:'.$request->ip().'|'.$request->email;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return ApiResponse::error(__('messages.login_rate_limited'), [], 429);
        }

        $user = User::query()->where('email', $request->email)->first();
        if ($user === null || ! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($key, 60);

            return ApiResponse::error(__('messages.invalid_credentials'), [], 401);
        }

        RateLimiter::clear($key);
        $token = $user->createToken('api')->plainTextToken;

        return ApiResponse::success([
            'user' => (new UserResource($user))->resolve(),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return ApiResponse::success(['message' => __('messages.logged_out')]);
    }

    public function me(Request $request)
    {
        return ApiResponse::success(new UserResource($request->user()));
    }

    public function updatePassword(PasswordUpdateRequest $request)
    {
        $user = $request->user();
        $user->update(['password' => $request->password]);
        $user->tokens()->delete();

        return ApiResponse::success(['message' => __('messages.password_updated')]);
    }
}
