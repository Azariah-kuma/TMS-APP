<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Auth\AuthenticateUserAction;
use App\Exceptions\InvalidPasswordResetTokenException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\SetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

/*
 * 認証関連のAPIエンドポイントを提供するコントローラー。
 */
final class AuthController extends Controller
{
    /**
     * HRからの招待メール（初回）またはパスワードを忘れた場合の再設定リンクから、
     * 新しいパスワードを設定する。Laravel標準のパスワードリセット機構をそのまま使う。
     */
    public function setPassword(SetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password): void {
                $user->forceFill(['password' => Hash::make($password)])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new InvalidPasswordResetTokenException('招待リンクが無効か、有効期限が切れています。');
        }

        $user = User::whereEmail($request->validated('email'))->firstOrFail();

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(new UserResource($this->loadUserContext($user)));
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

        return response()->json(new UserResource($this->loadUserContext($request->user())));
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
        return response()->json(new UserResource($this->loadUserContext($request->user())));
    }

    /**
     * UserResource（EmployeeResource経由）が必要とする部署・役職・is_managerまで、
     * EmployeeController::index/showと同じ深さでeager loadする。
     * ここが浅いと、部署・役職バッジがログイン直後の画面でだけ空欄になる。
     */
    private function loadUserContext(User $user): User
    {
        $user->load(['employee.currentAssignment.department', 'employee.currentAssignment.position']);
        $user->employee?->loadExists('currentDirectReportAssignments as is_manager');

        return $user;
    }
}
