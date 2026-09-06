<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use App\Models\Training;
use App\Models\TrainingEnrollment;
use App\Models\TrainingLesson;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

it('人事は研修にLessonを追加できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $training = Training::factory()->create();

    Sanctum::actingAs($hr->user);

    $this->postJson("/api/trainings/{$training->id}/lessons", [
        'title' => '第1章 イントロダクション',
        'position' => 1,
    ])->assertCreated()->assertJsonPath('title', '第1章 イントロダクション');
});

it('positionが文字列で送られてきても（multipart/form-dataでは常にそうなる）Lessonを作成できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $training = Training::factory()->create();

    Sanctum::actingAs($hr->user);

    // JSONではなくmultipartで送る点が重要（ファイル添付時と同じ経路で、positionが文字列になる）。
    $this->post("/api/trainings/{$training->id}/lessons", [
        'title' => '第1章',
        'position' => '2',
    ])->assertCreated()->assertJsonPath('position', 2);
});

it('人事はLessonの教材として動画ファイルを添付できる', function () {
    Storage::fake('public');

    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $training = Training::factory()->create();
    $video = UploadedFile::fake()->create('lesson.mp4', 5120, 'video/mp4');

    Sanctum::actingAs($hr->user);

    $response = $this->post("/api/trainings/{$training->id}/lessons", [
        'title' => '第1章 講義動画',
        'contents' => [$video],
    ])->assertCreated();

    $response->assertJsonPath('attachments.0.original_name', 'lesson.mp4')
        ->assertJsonPath('attachments.0.mime_type', 'video/mp4');

    expect($response->json('attachments.0.url'))->not->toBeNull();

    $lesson = TrainingLesson::where('title', '第1章 講義動画')->firstOrFail();
    Storage::disk('public')->assertExists($lesson->attachments->first()->path);
});

it('人事はLessonに複数の教材ファイルを一度に添付できる', function () {
    Storage::fake('public');

    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $training = Training::factory()->create();
    $video = UploadedFile::fake()->create('lesson.mp4', 5120, 'video/mp4');
    $pdf = UploadedFile::fake()->create('slide.pdf', 512, 'application/pdf');

    Sanctum::actingAs($hr->user);

    $response = $this->post("/api/trainings/{$training->id}/lessons", [
        'title' => '第1章',
        'contents' => [$video, $pdf],
    ])->assertCreated();

    $response->assertJsonCount(2, 'attachments');
    expect(collect($response->json('attachments'))->pluck('original_name')->all())
        ->toBe(['lesson.mp4', 'slide.pdf']);
});

it('対応していない形式の教材ファイルは拒否される', function () {
    Storage::fake('public');

    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $training = Training::factory()->create();
    $file = UploadedFile::fake()->create('malware.exe', 10, 'application/x-msdownload');

    Sanctum::actingAs($hr->user);

    $this->post("/api/trainings/{$training->id}/lessons", [
        'title' => '第1章',
        'contents' => [$file],
    ])->assertUnprocessable()->assertJsonValidationErrors('contents.0');
});

it('一般社員はLessonを追加できない', function () {
    $employee = createEmployeeWithAssignment();
    $training = Training::factory()->create();

    Sanctum::actingAs($employee->user);

    $this->postJson("/api/trainings/{$training->id}/lessons", ['title' => '第1章'])->assertForbidden();
});

it('ログイン済みの従業員なら誰でも研修のLesson一覧を取得できる', function () {
    $employee = createEmployeeWithAssignment();
    $training = Training::factory()->create();
    TrainingLesson::factory()->for($training)->count(2)->create();

    Sanctum::actingAs($employee->user);

    $this->getJson("/api/trainings/{$training->id}/lessons")->assertOk()->assertJsonCount(2);
});

it('受講中の従業員は自分のLessonにチェックを入れて進捗の更新を確認できる', function () {
    $employee = createEmployeeWithAssignment();
    $training = Training::factory()->create();
    $lessons = TrainingLesson::factory()->for($training)->count(2)->create();
    $enrollment = TrainingEnrollment::factory()->create([
        'employee_id' => $employee->id,
        'training_id' => $training->id,
    ]);

    Sanctum::actingAs($employee->user);

    $response = $this->putJson(
        "/api/training-enrollments/{$enrollment->id}/lessons/{$lessons[0]->id}",
    )->assertOk();

    $response->assertJsonPath('progress', 50)
        ->assertJsonPath('status', 'in_progress')
        ->assertJsonFragment(['completed_lesson_ids' => [$lessons[0]->id]]);

    $this->deleteJson("/api/training-enrollments/{$enrollment->id}/lessons/{$lessons[0]->id}")
        ->assertOk()
        ->assertJsonPath('progress', 0)
        ->assertJsonPath('status', 'not_started');
});

it('上司は部下のLessonにチェックを入れられない（閲覧のみ）', function () {
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
    $training = Training::factory()->create();
    $lesson = TrainingLesson::factory()->for($training)->create();
    $enrollment = TrainingEnrollment::factory()->create([
        'employee_id' => $subordinate->id,
        'training_id' => $training->id,
    ]);

    Sanctum::actingAs($manager->user);

    $this->putJson("/api/training-enrollments/{$enrollment->id}/lessons/{$lesson->id}")->assertForbidden();
});

it('受講記録の閲覧時に研修のLesson一覧が含まれ、フロントが手動進捗とLesson進捗を区別できる', function () {
    $employee = createEmployeeWithAssignment();
    $training = Training::factory()->create();
    $lesson = TrainingLesson::factory()->for($training)->create();
    $enrollment = TrainingEnrollment::factory()->create([
        'employee_id' => $employee->id,
        'training_id' => $training->id,
    ]);

    Sanctum::actingAs($employee->user);

    $this->getJson("/api/training-enrollments/{$enrollment->id}")
        ->assertOk()
        ->assertJsonPath('training.lessons.0.id', $lesson->id);

    $listed = collect($this->getJson('/api/training-enrollments')->assertOk()->json())
        ->firstWhere('id', $enrollment->id);

    expect($listed['training']['lessons'][0]['id'])->toBe($lesson->id);
});

it('チェック直後だけでなく、ページを読み込み直してもLessonの完了状態が保持される', function () {
    $employee = createEmployeeWithAssignment();
    $training = Training::factory()->create();
    $lesson = TrainingLesson::factory()->for($training)->create();
    $enrollment = TrainingEnrollment::factory()->create([
        'employee_id' => $employee->id,
        'training_id' => $training->id,
    ]);

    Sanctum::actingAs($employee->user);

    $this->putJson("/api/training-enrollments/{$enrollment->id}/lessons/{$lesson->id}")->assertOk();

    // 完了直後のレスポンスだけでなく、別リクエストで改めて取得した場合でも
    // completed_lesson_ids が読み込まれている必要がある（ページを再読み込みした状況を想定）。
    $this->getJson("/api/training-enrollments/{$enrollment->id}")
        ->assertOk()
        ->assertJsonFragment(['completed_lesson_ids' => [$lesson->id]]);

    $listed = collect($this->getJson('/api/training-enrollments')->assertOk()->json())
        ->firstWhere('id', $enrollment->id);

    expect($listed['completed_lesson_ids'])->toBe([$lesson->id]);
});

it('人事はLessonを削除できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $training = Training::factory()->create();
    $lesson = TrainingLesson::factory()->for($training)->create();

    Sanctum::actingAs($hr->user);

    $this->deleteJson("/api/trainings/{$training->id}/lessons/{$lesson->id}")->assertNoContent();

    expect(TrainingLesson::find($lesson->id))->toBeNull();
});

it('一般社員はLessonを削除できない', function () {
    $employee = createEmployeeWithAssignment();
    $training = Training::factory()->create();
    $lesson = TrainingLesson::factory()->for($training)->create();

    Sanctum::actingAs($employee->user);

    $this->deleteJson("/api/trainings/{$training->id}/lessons/{$lesson->id}")->assertForbidden();

    expect(TrainingLesson::find($lesson->id))->not->toBeNull();
});

it('別の研修に属するLessonの削除を試みると拒否される', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $training = Training::factory()->create();
    $otherLesson = TrainingLesson::factory()->create();

    Sanctum::actingAs($hr->user);

    $this->deleteJson("/api/trainings/{$training->id}/lessons/{$otherLesson->id}")
        ->assertStatus(422);

    expect(TrainingLesson::find($otherLesson->id))->not->toBeNull();
});

it('研修にLessonが存在する場合、手動での進捗更新は拒否される', function () {
    $employee = createEmployeeWithAssignment();
    $training = Training::factory()->create();
    TrainingLesson::factory()->for($training)->create();
    $enrollment = TrainingEnrollment::factory()->create([
        'employee_id' => $employee->id,
        'training_id' => $training->id,
    ]);

    Sanctum::actingAs($employee->user);

    $this->patchJson("/api/training-enrollments/{$enrollment->id}", ['progress' => 50])
        ->assertStatus(422);
});
