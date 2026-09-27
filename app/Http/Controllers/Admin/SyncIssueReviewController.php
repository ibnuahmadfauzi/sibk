<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewSyncIssueRequest;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ExternalSyncIssue;
use App\Models\ExternalTatibRecord;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\User;
use App\Services\SyncIssueReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class SyncIssueReviewController extends Controller
{
    public function show(Request $request, ExternalSyncIssue $issue): Response
    {
        Gate::forUser($request->user())->authorize('manageDataMaster');
        $issue->load('syncRun:id,source,started_at');
        $record = $issue->entity_type === 'etatib_record'
            ? ExternalTatibRecord::query()->where('source_identifier', $issue->source_identifier)->with('student:id,name,nisn')->first()
            : null;
        $masterStudent = $record?->student ?? ($issue->entity_type === 'etatib_record'
            ? Student::query()->where('nisn', $issue->nisn ?: $record?->nisn)->first()
            : null);
        $memberships = $masterStudent === null || $record?->occurred_at === null ? collect() : StudentClassMembership::query()
            ->where('student_id', $masterStudent->getKey())
            ->active()
            ->whereHas('academicYear', fn ($years) => $years
                ->whereDate('starts_on', '<=', $record->occurred_at->toDateString())
                ->whereDate('ends_on', '>=', $record->occurred_at->toDateString()))
            ->with(['classroom:id,name', 'academicYear:id,name'])
            ->get();
        $local = null;
        if ($issue->issue_code === 'unmatched_local_record' && str_starts_with((string) $issue->source_identifier, 'local:')) {
            $id = (int) substr($issue->source_identifier, 6);
            $local = match ($issue->entity_type) {
                'student' => ($student = Student::query()->find($id)) === null ? null : $student->name.' (NISN '.$student->nisn.')',
                'academic_year' => AcademicYear::query()->find($id)?->name,
                'classroom' => ($classroom = Classroom::query()->with('academicYear:id,name')->find($id)) === null ? null : $classroom->name.' ('.$classroom->academicYear?->name.')',
                'membership' => ($membership = StudentClassMembership::query()->with(['student:id,name', 'classroom:id,name', 'academicYear:id,name'])->find($id)) === null
                    ? null : $membership->student?->name.' — '.$membership->classroom?->name.' ('.$membership->academicYear?->name.')',
                default => null,
            };
        }
        $reviewer = User::query()->find(data_get($issue->details, 'review.reviewed_by'));

        return response()->view('pages.data-master.sync-issue-review', compact('issue', 'record', 'masterStudent', 'memberships', 'local', 'reviewer'))
            ->header('Cache-Control', 'no-store');
    }

    public function update(ReviewSyncIssueRequest $request, ExternalSyncIssue $issue, SyncIssueReviewService $service): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $service->review($issue, $actor, $request->string('status')->toString(), $request->string('note')->toString());

        return redirect()->route('data-master.sync-issues.show', $issue)->with('success', 'Pemeriksaan tersimpan. Masalah tetap terbuka sampai datanya cocok.');
    }
}
