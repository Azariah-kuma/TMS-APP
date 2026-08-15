<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Auth\AuthenticateUserAction;
use App\Actions\Auth\RegisterUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/*
 * 認証関連のAPIエンドポイントを提供するコントローラー。
 */
final class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        $validated = $request->validated();

        $user = $action->execute(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
        );

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(new UserResource($user->load('employee.currentAssignment')), Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request, AuthenticateUserAction $action): JsonResponse
    {
        $validated = $request->validated();

        $action->execute(
            email: $validated['email'],
            password: $validated['password'],
            remember: $request->boolean('remember'),
        );

        // セッション固定化攻撃を防ぐため、認証成功後は必ずセッションIDを再生成する
        $request->session()->regenerate();

        return response()->json(new UserResource($request->user()->load('employee.currentAssignment')));
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()->load('employee.currentAssignment')));
    }
}
