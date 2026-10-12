<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExternalSyncIssue;
use App\Models\ExternalTatibRecord;
use App\Models\StudentClassMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class SyncIssueReviewService
{
    public function __construct(private readonly AuditService $auditService) {}

    public function review(ExternalSyncIssue $issue, User $actor, string $action, ?int $membershipId, ?string $note): void
    {
        Gate::forUser($actor)->authorize('manageDataMaster');

        DB::transaction(function () use ($issue, $actor, $action, $membershipId, $note): void {
            $locked = ExternalSyncIssue::query()->lockForUpdate()->findOrFail($issue->getKey());
            if ($locked->resolved_at !== null) {
                throw ValidationException::withMessages(['action' => 'Masalah ini sudah selesai. Muat ulang daftar untuk melihat status terbaru.']);
            }

            $choice = null;
            if ($action !== 'source_correction') {
                if ($locked->issue_code !== 'student_classroom_mismatch') {
                    throw ValidationException::withMessages(['action' => 'Pilihan kelas hanya tersedia untuk perbedaan kelas.']);
                }
                $record = ExternalTatibRecord::query()->where('source_identifier', $locked->source_identifier)->first();
                if ($record?->student_id === null || $record->occurred_at === null || blank($record->source_classroom_name)) {
                    throw ValidationException::withMessages(['action' => 'Data kejadian belum lengkap untuk memilih kelas.']);
                }
                $date = $record->occurred_at->toDateString();
                $memberships = StudentClassMembership::query()->where('student_id', $record->student_id)->active()
                    ->whereHas('academicYear', fn ($years) => $years->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date))
                    ->with(['classroom:id,name', 'academicYear:id,name'])->get();
                if ($memberships->isEmpty()) {
                    throw ValidationException::withMessages(['action' => 'Kelas sekolah pada tanggal kejadian belum tercatat.']);
                }
                $membership = $action === 'use_school' ? $memberships->firstWhere('id', $membershipId) : null;
                if ($action === 'use_school' && ($membership === null || $membership->classroom === null)) {
                    throw ValidationException::withMessages(['membership_id' => 'Pilih kelas sekolah pada tanggal kejadian.']);
                }
                $choice = [
                    'classroom' => $membership?->classroom?->name ?? $record->source_classroom_name,
                    'classroom_id' => $membership?->classroom_id,
                    'academic_year_id' => $membership?->academic_year_id,
                    'signature' => self::classroomSignature($record->student_id, $date, $record->source_classroom_name, $memberships->pluck('classroom.name')->filter()->all()),
                ];
            }

            $details = $locked->details ?? [];
            $previous = $details['review'] ?? null;
            $details['review'] = [
                'action' => $action,
                'status' => $choice === null ? 'source_correction' : 'resolved',
                'choice' => $choice,
                'note' => trim($note ?? ''),
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => now()->toIso8601String(),
            ];
            $locked->update([
                'details' => $details,
                'resolved_student_id' => $choice === null ? $locked->resolved_student_id : $record->student_id,
                'resolved_by' => $choice === null ? $locked->resolved_by : $actor->getKey(),
                'resolved_at' => $choice === null ? null : now(),
            ]);
            $this->auditService->record('sync_issue.reviewed', $locked, 'Admin memeriksa masalah sinkronisasi.', $actor,
                ['review' => $previous], ['review' => $details['review']]);
        });
    }

    /** @param array<int, string> $schoolClasses */
    public static function classroomSignature(int $studentId, string $date, string $sourceClass, array $schoolClasses): string
    {
        $normalize = static fn (string $name): string => mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name) ?? $name));
        $schoolClasses = array_map($normalize, $schoolClasses);
        sort($schoolClasses);

        return hash('sha256', json_encode([$studentId, $date, $normalize($sourceClass), $schoolClasses], JSON_THROW_ON_ERROR));
    }
}
