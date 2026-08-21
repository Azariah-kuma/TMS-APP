<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\SetInitialPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/*
 * ユーザーのモデルクラス。
 */
#[Fillable(['last_name', 'first_name', 'last_name_kana', 'first_name_kana', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** 姓・名を連結した表示用フルネーム。 */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): string => "{$this->last_name}{$this->first_name}",
        );
    }

    /** 姓・名のふりがなを連結した表示用文字列。 */
    protected function nameKana(): Attribute
    {
        return Attribute::make(
            get: fn (): string => "{$this->last_name_kana}{$this->first_name_kana}",
        );
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * オンボーディング招待・パスワードリセット、どちらの場合も同じLaravel標準の
     * トークン機構を使うため、送信する通知だけをフロントエンド向けにカスタマイズする。
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new SetInitialPasswordNotification($token));
    }
}
