<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExternalSyncIssue;
use App\Models\IdentityReconciliation;
use App\Models\ReferenceValue;
use App\Models\Student;
use App\Models\TemporaryStudent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentIdentityService
{
    public function __construct(private readonly AuditService $auditService) {}

    public function createTemporary(string $nisn, string $inputName, User $creator): TemporaryStudent
    {
        if (Student::query()->where('nisn', $nisn)->exists()) {
            throw ValidationException::withMessages([
                'temporary_nisn' => 'NISN sudah tersedia pada data master. Pilih murid resmi.',
            ]);
        }

        $status = $this->status('menunggu_rekonsiliasi');

        return DB::transaction(function () use ($nisn, $inputName, $creator, $status): TemporaryStudent {
            $temporary = TemporaryStudent::query()->create([
                'nisn' => $nisn,
                'input_name' => $inputName,
                'created_by' => $creator->getKey(),
                'reconciliation_status_id' => $status->getKey(),
            ]);

            $this->auditService->record(
                action: 'temporary_student.created',
                auditable: $temporary,
                summary: 'Identitas murid sementara dibuat untuk kebutuhan layanan.',
                actor: $creator,
                after: ['nisn' => $nisn, 'input_name' => $inputName, 'status' => $status->code],
            );

            return $temporary;
        });
    }

    public function reconcilePending(?User $actor = null): int
    {
        $count = 0;

        TemporaryStudent::query()
            ->whereNull('reconciled_student_id')
            ->orderBy('id')
            ->each(function (TemporaryStudent $temporary) use ($actor, &$count): void {
                $this->reconcile($temporary, $actor);
                $count++;
            });

        return $count;
    }

    public function reconcile(TemporaryStudent $temporary, ?User $actor = null): IdentityReconciliation
    {
        return DB::transaction(function () use ($temporary, $actor): IdentityReconciliation {
            $temporary = TemporaryStudent::query()->lockForUpdate()->findOrFail($temporary->getKey());
            $student = Student::query()->where('nisn', $temporary->nisn)->first();
            $unresolvedIssues = ExternalSyncIssue::query()
                ->where('nisn', $temporary->nisn)
                ->whereNull('resolved_at');

            if ($student !== null) {
                $status = $this->status('terekonsiliasi');
                $result = 'Identitas sementara berhasil ditautkan ke data master Dapodik.';
                $conflict = null;
                $unresolvedIssues->update([
                    'resolved_student_id' => $student->getKey(),
                    'resolved_by' => $actor?->getKey(),
                    'resolved_at' => now(),
                ]);
                $temporary->update([
                    'reconciliation_status_id' => $status->getKey(),
                    'reconciled_student_id' => $student->getKey(),
                    'reconciled_by' => $actor?->getKey(),
                    'reconciled_at' => now(),
                ]);
            } elseif ($unresolvedIssues->exists()) {
                $status = $this->status('ditahan_konflik');
                $result = 'Rekonsiliasi ditahan karena terdapat konflik NISN pada data sumber.';
                $conflict = $unresolvedIssues->pluck('issue_code')->unique()->values()->all();
                $temporary->update(['reconciliation_status_id' => $status->getKey()]);
            } else {
                $status = $this->status('menunggu_rekonsiliasi');
                $result = 'NISN belum ditemukan pada data master Dapodik.';
                $conflict = null;
                $temporary->update(['reconciliation_status_id' => $status->getKey()]);
            }

            $reconciliation = IdentityReconciliation::query()->create([
                'temporary_student_id' => $temporary->getKey(),
                'source_nisn' => $temporary->nisn,
                'student_id' => $student?->getKey(),
                'official_name' => $student?->name,
                'status_id' => $status->getKey(),
                'result' => $result,
                'checked_by' => $actor?->getKey(),
                'conflict_details' => $conflict,
                'checked_at' => now(),
            ]);

            $this->auditService->record(
                action: 'student_identity.reconciled',
                auditable: $temporary,
                summary: $result,
                actor: $actor,
                before: ['nisn' => $temporary->nisn, 'input_name' => $temporary->input_name],
                after: [
                    'status' => $status->code,
                    'student_id' => $student?->getKey(),
                    'official_name' => $student?->name,
                ],
            );

            return $reconciliation;
        });
    }

    private function status(string $code): ReferenceValue
    {
        return ReferenceValue::query()
            ->where('category', 'reconciliation_status')
            ->where('code', $code)
            ->firstOrFail();
    }
}
