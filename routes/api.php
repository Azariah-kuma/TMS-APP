<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DelegationController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\EmployeeAssignmentController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\TrainingController;
use App\Http\Controllers\Api\TrainingEnrollmentController;
use App\Http\Controllers\Api\TrainingLessonCompletionController;
use App\Http\Controllers\Api\TrainingLessonController;
use Illuminate\Support\Facades\Route;

// ログイン・パスワード設定試行のブルートフォース攻撃対策として1分あたり6回までに制限
Route::middleware('throttle:6,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/set-password', [AuthController::class, 'setPassword']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::post('/departments', [DepartmentController::class, 'store']);

    Route::get('/positions', [PositionController::class, 'index']);
    Route::post('/positions', [PositionController::class, 'store']);

    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::post('/employees', [EmployeeController::class, 'store']);
    Route::get('/employees/{employee}', [EmployeeController::class, 'show']);
    // 招待メールの再送信は、HR権限があれば無制限に連打できてしまわないよう別途レート制限する
    Route::post('/employees/{employee}/resend-invite', [EmployeeController::class, 'resendInvite'])
        ->middleware('throttle:6,1');

    // 部署・役職・上司の異動履歴
    Route::get('/employees/{employee}/assignments', [EmployeeAssignmentController::class, 'index']);
    Route::post('/employees/{employee}/assignments', [EmployeeAssignmentController::class, 'store']);

    // 上司権限の一時的な委任（課長代理など）
    Route::get('/employees/{employee}/delegations', [DelegationController::class, 'index']);
    Route::post('/employees/{employee}/delegations', [DelegationController::class, 'store']);
    Route::delete('/delegations/{delegation}', [DelegationController::class, 'destroy']);

    // 研修の受講登録（人事が対象従業員に割り当てる）
    Route::post('/employees/{employee}/training-enrollments', [TrainingEnrollmentController::class, 'store']);

    // 研修の一括受講登録（部署指定、またはdepartment_id省略で全社）
    Route::post('/trainings/{training}/bulk-enroll', [TrainingEnrollmentController::class, 'bulkEnroll']);

    Route::get('/trainings', [TrainingController::class, 'index']);
    Route::post('/trainings', [TrainingController::class, 'store']);
    Route::get('/trainings/{training}', [TrainingController::class, 'show']);
    Route::patch('/trainings/{training}', [TrainingController::class, 'update']);
    Route::delete('/trainings/{training}', [TrainingController::class, 'destroy']);

    // 研修を構成するLesson（教材）一覧
    Route::get('/trainings/{training}/lessons', [TrainingLessonController::class, 'index']);
    Route::post('/trainings/{training}/lessons', [TrainingLessonController::class, 'store']);

    // 受講進捗（ロールに応じて閲覧範囲がスコープされる）
    Route::get('/training-enrollments', [TrainingEnrollmentController::class, 'index']);
    Route::get('/training-enrollments/{trainingEnrollment}', [TrainingEnrollmentController::class, 'show']);
    Route::patch('/training-enrollments/{trainingEnrollment}', [TrainingEnrollmentController::class, 'update']);
    Route::delete('/training-enrollments/{trainingEnrollment}', [TrainingEnrollmentController::class, 'destroy']);

    // Lesson単位の完了チェック
    Route::put(
        '/training-enrollments/{trainingEnrollment}/lessons/{trainingLesson}',
        [TrainingLessonCompletionController::class, 'complete'],
    );
    Route::delete(
        '/training-enrollments/{trainingEnrollment}/lessons/{trainingLesson}',
        [TrainingLessonCompletionController::class, 'incomplete'],
    );
});
