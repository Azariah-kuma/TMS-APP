<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/*
 * 監査ログに関するAPIエンドポイントを提供するコントローラー。
 */
final class AuditLogController extends Controller
{
    /**
     * 監査ログの一覧（新しい順）。
     * auditable_type（例: Training）・auditable_id を指定すると、対象を絞り込める。
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', AuditLog::class);

        $logs = AuditLog::query()
            ->with('actor.user')
            ->when(
                $request->filled('auditable_type'),
                fn ($query) => $query->where('auditable_type', 'App\\Models\\'.$request->string('auditable_type')),
            )
            ->when(
                $request->filled('auditable_id'),
                fn ($query) => $query->where('auditable_id', (int) $request->query('auditable_id')),
            )
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'data' => AuditLogResource::collection($logs->items()),
            'current_page' => $logs->currentPage(),
            'last_page' => $logs->lastPage(),
            'total' => $logs->total(),
        ]);
    }
}
