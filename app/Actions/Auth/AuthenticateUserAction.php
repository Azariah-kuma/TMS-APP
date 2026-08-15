<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * ユーザー認証アクション
 */
final class AuthenticateUserAction
{
    /**
     * @throws ValidationException 認証情報が一致しない場合
     */
    public function execute(string $email, string $password, bool $remember = false): void
    {
        if (! Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }
    }
}
