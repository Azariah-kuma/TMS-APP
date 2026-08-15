<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
 * User(ユーザー)のリソースクラス
 */

/** @mixin User */
final class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'employee' => $this->whenLoaded(
                'employee',
                fn () => $this->employee ? new EmployeeResource($this->employee) : null,
            ),
        ];
    }
}
