<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\TemporaryStudent;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class AcademicYearPreparationService
{
    public function __construct(
        private readonly ProvisionalRosterCsvParser $csvParser,
        private readonly AuditService $auditService,
        private readonly AcademicYearRolloverQuery $rolloverQuery,
    ) {}

    /** @param array<string, mixed> $data */
    public function prepareAcademicYear(array $data, User $actor): AcademicYear
    {
        Gate::forUser($actor)->authorize('manageDataMaster');

        $normalized = [
            'name' => trim((string) ($data['name'] ?? '')),
            'starts_on' => trim((string) ($data['starts_on'] ?? '')),
            'ends_on' => trim((string) ($data['ends_on'] ?? '')),
            'preparation_reference' => trim((string) ($data['preparation_reference'] ?? '')),
        ];
        $validated = Validator::make($normalized, [
            'name' => ['required', 'string', 'max:20'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['required', 'date_format:Y-m-d', 'after:starts_on'],
            'preparation_reference' => ['required', 'string', 'max:500'],
        ])->validate();

        return DB::transaction(function () use ($validated, $actor): AcademicYear {
            if (AcademicYear::query()->where('name', $validated['name'])->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['name' => 'Nama tahun ajaran sudah digunakan.']);
            }

            if (AcademicYear::query()
                ->whereDate('starts_on', $validated['starts_on'])
                ->whereDate('ends_on', $validated['ends_on'])
                ->lockForUpdate()
                ->exists()) {
                throw ValidationException::withMessages(['period' => 'Periode tahun ajaran sudah digunakan.']);
            }

            $year = AcademicYear::query()->create([
                ...$validated,
                'is_active' => false,
                'master_source' => AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
                'source_confirmed_at' => null,
                'prepared_by' => $actor->getKey(),
            ]);

            $this->auditService->record(
                action: 'academic_year.prepared',
                auditable: $year,
                summary: 'Tahun ajaran persiapan dibuat berdasarkan dokumen resmi sekolah.',
                actor: $actor,
                after: $this->academicYearSnapshot($year),
            );

            return $year;
        });
    }

    public function importRoster(
        AcademicYear $academicYear,
        UploadedFile $file,
        User $actor,
    ): ProvisionalRosterImportResult {
        Gate::forUser($actor)->authorize('manageDataMaster');

        try {
            $currentYear = AcademicYear::query()->findOrFail($academicYear->getKey());
            $this->assertImportable($currentYear);
            $rows = $this->csvParser->parse($file);

            return DB::transaction(function () use ($academicYear, $rows, $actor): ProvisionalRosterImportResult {
                $year = AcademicYear::query()->lockForUpdate()->findOrFail($academicYear->getKey());
                $this->assertImportable($year);

                /** @var array<string, Student|null> $studentsByNisn */
                $studentsByNisn = [];
                foreach ($rows as $row) {
                    $candidates = Student::query()
                        ->whereRaw('TRIM(nisn) = ?', [$row['nisn']])
                        ->lockForUpdate()
                        ->limit(3)
                        ->get();
                    $students = $candidates->filter(
                        fn (Student $candidate): bool => $candidate->nisn === $row['nisn'],
                    );

                    if ($students->count() > 1 || $candidates->count() !== $students->count()) {
                        throw ValidationException::withMessages([
                            'nisn' => 'NISN memiliki lebih dari satu kandidat lokal dan harus diperiksa.',
                        ]);
                    }

                    $student = $students->first();
                    $studentsByNisn['nisn:'.$row['nisn']] = $student;
                    if ($student !== null) {
                        $this->assertMembershipMatchesRoster($student, $year, $row['classroom']);
                    }
                }

                $studentsCreated = 0;
                $studentsMatched = 0;
                $classroomsCreated = 0;
                $membershipsCreated = 0;
                $membershipsUnchanged = 0;
                /** @var array<string, Classroom> $classroomsByName */
                $classroomsByName = [];

                foreach ($rows as $row) {
                    $classroomKey = mb_strtolower($row['classroom']);
                    $classroom = $classroomsByName[$classroomKey] ?? null;

                    if ($classroom === null) {
                        $matchingClassrooms = Classroom::query()
                            ->where('academic_year_id', $year->getKey())
                            ->whereRaw('LOWER(name) = ?', [$classroomKey])
                            ->lockForUpdate()
                            ->limit(2)
                            ->get();
                        if ($matchingClassrooms->count() > 1) {
                            throw ValidationException::withMessages([
                                'rombel' => 'Nama rombel memiliki lebih dari satu kandidat lokal.',
                            ]);
                        }

                        $classroom = $matchingClassrooms->first();
                        if ($classroom === null) {
                            $classroom = Classroom::query()->create([
                                'academic_year_id' => $year->getKey(),
                                'name' => $row['classroom'],
                                'grade_level' => null,
                                'major' => null,
                                'is_active' => true,
                                'master_source' => Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL,
                                'source_confirmed_at' => null,
                            ]);
                            $classroomsCreated++;
                            $this->auditService->record(
                                action: 'provisional_classroom.created',
                                auditable: $classroom,
                                summary: 'Rombel persiapan dibuat.',
                                actor: $actor,
                                after: [
                                    'academic_year_id' => $year->getKey(),
                                    'master_source' => $classroom->master_source,
                                ],
                            );
                        }
                        $classroomsByName[$classroomKey] = $classroom;
                    }

                    $student = $studentsByNisn['nisn:'.$row['nisn']];
                    if ($student === null) {
                        $student = Student::query()->create([
                            'nisn' => $row['nisn'],
                            'name' => $row['name'],
                            'is_active' => true,
                            'master_source' => Student::MASTER_SOURCE_SCHOOL_PROVISIONAL,
                            'source_confirmed_at' => null,
                        ]);
                        $studentsCreated++;
                        $this->auditService->record(
                            action: 'provisional_student.created',
                            auditable: $student,
                            summary: 'Murid persiapan dibuat.',
                            actor: $actor,
                            after: ['master_source' => $student->master_source],
                        );
                    } else {
                        $studentsMatched++;
                    }

                    $membership = StudentClassMembership::query()
                        ->where('student_id', $student->getKey())
                        ->where('academic_year_id', $year->getKey())
                        ->where('classroom_id', $classroom->getKey())
                        ->lockForUpdate()
                        ->first();

                    if ($membership === null) {
                        $membership = StudentClassMembership::query()->create([
                            'student_id' => $student->getKey(),
                            'classroom_id' => $classroom->getKey(),
                            'academic_year_id' => $year->getKey(),
                            'effective_from' => $year->starts_on?->toDateString(),
                            'effective_until' => null,
                            'is_active' => true,
                            'master_source' => StudentClassMembership::MASTER_SOURCE_SCHOOL_PROVISIONAL,
                            'source_confirmed_at' => null,
                        ]);
                        $membershipsCreated++;
                        $this->auditService->record(
                            action: 'provisional_membership.created',
                            auditable: $membership,
                            summary: 'Keanggotaan rombel persiapan dibuat.',
                            actor: $actor,
                            after: [
                                'student_id' => $student->getKey(),
                                'classroom_id' => $classroom->getKey(),
                                'academic_year_id' => $year->getKey(),
                                'master_source' => $membership->master_source,
                            ],
                        );
                    } else {
                        $membershipsUnchanged++;
                    }
                }

                $result = new ProvisionalRosterImportResult(
                    rows: count($rows),
                    studentsCreated: $studentsCreated,
                    studentsMatched: $studentsMatched,
                    classroomsCreated: $classroomsCreated,
                    membershipsCreated: $membershipsCreated,
                    membershipsUnchanged: $membershipsUnchanged,
                );

                $this->auditService->record(
                    action: 'academic_year.roster_imported',
                    auditable: $year,
                    summary: 'Daftar persiapan murid diproses tanpa menyimpan berkas mentah.',
                    actor: $actor,
                    after: [
                        'rows' => $result->rows,
                        'students_created' => $result->studentsCreated,
                        'students_matched' => $result->studentsMatched,
                        'classrooms_created' => $result->classroomsCreated,
                        'memberships_created' => $result->membershipsCreated,
                        'memberships_unchanged' => $result->membershipsUnchanged,
                    ],
                );

                return $result;
            });
        } catch (ValidationException $exception) {
            $this->recordRosterImportFailure(
                $academicYear,
                $actor,
                $this->safeFailureCode($exception),
            );

            throw $exception;
        } catch (Throwable $exception) {
            $this->recordRosterImportFailure($academicYear, $actor, 'processing_failed');

            throw $exception;
        }
    }

    public function activate(AcademicYear $academicYear, User $actor): AcademicYear
    {
        Gate::forUser($actor)->authorize('create', TeacherAssignment::class);

        return DB::transaction(function () use ($academicYear, $actor): AcademicYear {
            $year = AcademicYear::query()->lockForUpdate()->findOrFail($academicYear->getKey());
            if ($year->is_active) {
                throw ValidationException::withMessages(['academic_year' => 'Tahun ajaran sudah aktif.']);
            }
            if ($year->starts_on === null || $year->ends_on === null || $year->ends_on->lte($year->starts_on)) {
                throw ValidationException::withMessages(['period' => 'Tanggal tahun ajaran harus lengkap dan berurutan.']);
            }
            if (now()->startOfDay()->lt($year->starts_on) || now()->startOfDay()->gt($year->ends_on)) {
                throw ValidationException::withMessages([
                    'period' => 'Tahun ajaran hanya dapat diaktifkan selama periode berlakunya.',
                ]);
            }

            $assessment = $this->activationStructure($year, lockForUpdate: true);
            if ($assessment['blocking'] !== []) {
                $field = array_key_first($assessment['blocking']);

                throw ValidationException::withMessages([
                    $field => $assessment['blocking'][$field],
                ]);
            }

            $previousYears = AcademicYear::query()
                ->whereKeyNot($year->getKey())
                ->where('is_active', true)
                ->lockForUpdate()
                ->get();
            foreach ($previousYears as $previousYear) {
                $before = $this->academicYearSnapshot($previousYear);
                $previousYear->update(['is_active' => false]);
                $this->auditService->record(
                    action: 'academic_year.deactivated',
                    auditable: $previousYear,
                    summary: 'Tahun ajaran lama ditutup dari penggunaan operasional.',
                    actor: $actor,
                    before: $before,
                    after: $this->academicYearSnapshot($previousYear->refresh()),
                );
            }

            $before = $this->academicYearSnapshot($year);
            $year->update([
                'is_active' => true,
                'activated_by' => $actor->getKey(),
                'activated_at' => now(),
            ]);
            $this->auditService->record(
                action: 'academic_year.activated',
                auditable: $year,
                summary: 'Tahun ajaran diaktifkan setelah rombel dan penugasan diperiksa.',
                actor: $actor,
                before: $before,
                after: $this->academicYearSnapshot($year->refresh()),
            );

            return $year->refresh();
        });
    }

    /**
     * @return array{
     *     ready: bool,
     *     state: 'active'|'ended'|'not_ready'|'ready'|'scheduled',
     *     available_from: ?string,
     *     issues: list<string>,
     *     warnings: list<string>,
     *     rollover: AcademicYearRolloverSummary,
     *     classrooms: Collection<int, array{
     *         classroom: Classroom,
     *         student_count: int,
     *         assignment_count: int,
     *         teacher_name: ?string,
     *         ready: bool
     *     }>
     * }
     */
    public function activationReadiness(AcademicYear $academicYear): array
    {
        $blocking = [];
        $warnings = [];
        $hasValidPeriod = $academicYear->starts_on !== null
            && $academicYear->ends_on !== null
            && $academicYear->ends_on->gt($academicYear->starts_on);
        if (! $hasValidPeriod) {
            $blocking['period'] = 'Tanggal tahun ajaran belum lengkap atau belum berurutan.';
        }

        $assessment = $this->activationStructure($academicYear);
        $blocking += $assessment['blocking'];
        $issues = array_values($blocking);
        $readinessRows = $assessment['classrooms'];

        $rollover = $this->rolloverQuery->summarize($academicYear);
        if ($rollover->needsConfirmationCount() > 0) {
            $warnings[] = sprintf(
                '%d murid dari tahun ajaran sebelumnya perlu dikonfirmasi.',
                $rollover->needsConfirmationCount(),
            );
        }
        $usesProvisionalData = $academicYear->master_source === AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL
            || Classroom::query()
                ->where('academic_year_id', $academicYear->getKey())
                ->where('master_source', Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL)
                ->exists()
            || StudentClassMembership::query()
                ->where('academic_year_id', $academicYear->getKey())
                ->where(function ($memberships): void {
                    $memberships
                        ->where('master_source', StudentClassMembership::MASTER_SOURCE_SCHOOL_PROVISIONAL)
                        ->orWhereHas('student', fn ($students) => $students
                            ->where('master_source', Student::MASTER_SOURCE_SCHOOL_PROVISIONAL));
                })
                ->exists();
        if ($usesProvisionalData) {
            $warnings[] = 'Data tahun ajaran masih menggunakan sumber persiapan sementara.';
        }
        if (TemporaryStudent::query()->whereNull('reconciled_student_id')->exists()) {
            $warnings[] = 'Identitas sementara masih menunggu rekonsiliasi.';
        }

        $today = now()->startOfDay();
        $state = match (true) {
            $academicYear->is_active => 'active',
            $hasValidPeriod && $academicYear->ends_on->lt($today) => 'ended',
            $issues !== [] => 'not_ready',
            $hasValidPeriod && $academicYear->starts_on->gt($today) => 'scheduled',
            default => 'ready',
        };

        return [
            'ready' => $state === 'ready',
            'state' => $state,
            'available_from' => $state === 'scheduled'
                ? $academicYear->starts_on?->toDateString()
                : null,
            'issues' => $issues,
            'warnings' => $warnings,
            'rollover' => $rollover,
            'classrooms' => $readinessRows,
        ];
    }

    /**
     * @return array{
     *     blocking: array<string, string>,
     *     classrooms: Collection<int, array{
     *         classroom: Classroom,
     *         student_count: int,
     *         assignment_count: int,
     *         teacher_name: ?string,
     *         ready: bool
     *     }>
     * }
     */
    private function activationStructure(AcademicYear $academicYear, bool $lockForUpdate = false): array
    {
        $classroomsQuery = Classroom::query()
            ->where('academic_year_id', $academicYear->getKey())
            ->where('is_active', true)
            ->orderBy('name');
        if ($lockForUpdate) {
            $classroomsQuery->lockForUpdate();
        }

        /** @var Collection<int, Classroom> $classrooms */
        $classrooms = $classroomsQuery->get();
        $membershipsQuery = StudentClassMembership::query()
            ->where('academic_year_id', $academicYear->getKey())
            ->whereIn('classroom_id', $classrooms->modelKeys())
            ->where('is_active', true)
            ->whereHas('student', fn ($students) => $students->where('is_active', true));
        if ($lockForUpdate) {
            $membershipsQuery->lockForUpdate();
        }
        $activeMemberships = $membershipsQuery->get();

        $readinessRows = $classrooms->map(function (Classroom $classroom) use ($academicYear, $activeMemberships, $lockForUpdate): array {
            $studentCount = $activeMemberships->where('classroom_id', $classroom->getKey())->count();
            $assignmentsQuery = TeacherAssignment::query()
                ->with('teacher.roles')
                ->where('classroom_id', $classroom->getKey())
                ->where('academic_year_id', $academicYear->getKey())
                ->when(
                    $academicYear->starts_on !== null,
                    fn ($query) => $query
                        ->whereDate('effective_from', '<=', $academicYear->starts_on->toDateString())
                        ->where(function ($period) use ($academicYear): void {
                            $period->whereNull('effective_until')
                                ->orWhereDate('effective_until', '>=', $academicYear->starts_on->toDateString());
                        }),
                    fn ($query) => $query->whereRaw('1 = 0'),
                );
            if ($lockForUpdate) {
                $assignmentsQuery->lockForUpdate();
            }
            $assignments = $assignmentsQuery->get();
            $assignment = $assignments->first();
            $assignmentReady = $assignments->count() === 1
                && $assignment !== null
                && $assignment->teacher->is_active
                && $assignment->teacher->hasRole('guru_bk');

            return [
                'classroom' => $classroom,
                'student_count' => $studentCount,
                'assignment_count' => $assignments->count(),
                'teacher_name' => $assignmentReady ? $assignment->teacher->name : null,
                'ready' => $studentCount > 0 && $assignmentReady,
            ];
        });

        $blocking = [];
        if ($classrooms->isEmpty()) {
            $blocking['classrooms'] = 'Belum ada rombel aktif pada tahun ajaran ini.';
        }
        if ($readinessRows->contains(fn (array $row): bool => $row['student_count'] === 0)) {
            $blocking['students'] = 'Setiap rombel aktif harus memiliki minimal satu murid aktif.';
        }
        if ($activeMemberships->groupBy('student_id')->contains(
            fn (Collection $memberships): bool => $memberships->count() > 1,
        )) {
            $blocking['memberships'] = 'Setiap murid hanya boleh memiliki satu keanggotaan aktif pada tahun ajaran target.';
        }
        if ($readinessRows->contains(
            fn (array $row): bool => $row['assignment_count'] !== 1 || $row['teacher_name'] === null,
        )) {
            $blocking['assignments'] = 'Setiap rombel harus memiliki tepat satu Guru BK aktif sejak awal tahun ajaran.';
        }

        return [
            'blocking' => $blocking,
            'classrooms' => $readinessRows,
        ];
    }

    private function assertImportable(AcademicYear $academicYear): void
    {
        if ($academicYear->master_source !== AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL) {
            throw ValidationException::withMessages([
                'academic_year' => 'Daftar persiapan hanya dapat diimpor ke tahun ajaran sementara.',
            ]);
        }
        if ($academicYear->is_active) {
            throw ValidationException::withMessages([
                'academic_year' => 'Daftar persiapan hanya dapat diimpor sebelum tahun ajaran diaktifkan.',
            ]);
        }
        if ($academicYear->starts_on === null || $academicYear->ends_on === null) {
            throw ValidationException::withMessages([
                'academic_year' => 'Periode tahun ajaran harus lengkap sebelum impor.',
            ]);
        }
    }

    private function assertMembershipMatchesRoster(
        Student $student,
        AcademicYear $academicYear,
        string $classroomName,
    ): void {
        $memberships = StudentClassMembership::query()
            ->with('classroom')
            ->where('student_id', $student->getKey())
            ->where('academic_year_id', $academicYear->getKey())
            ->lockForUpdate()
            ->limit(2)
            ->get();

        $hasCrossYearClassroom = $memberships->contains(
            fn (StudentClassMembership $membership): bool => $membership->classroom === null
                || $membership->classroom->academic_year_id !== $academicYear->getKey(),
        );

        if ($hasCrossYearClassroom
            || $memberships->count() > 1
            || ($memberships->count() === 1
                && mb_strtolower($memberships->first()->classroom->name) !== mb_strtolower($classroomName))) {
            throw ValidationException::withMessages([
                'rombel' => 'Rombel murid pada tahun ajaran ini berbeda dengan daftar persiapan.',
            ]);
        }
    }

    private function safeFailureCode(ValidationException $exception): string
    {
        return match (array_key_first($exception->errors())) {
            'file' => 'invalid_csv',
            'academic_year' => 'academic_year_not_importable',
            'nisn' => 'student_identity_conflict',
            'rombel' => 'classroom_membership_conflict',
            default => 'validation_failed',
        };
    }

    private function recordRosterImportFailure(
        AcademicYear $academicYear,
        User $actor,
        string $failureCode,
    ): void {
        $this->auditService->record(
            action: 'academic_year.roster_import_failed',
            auditable: $academicYear,
            summary: 'Impor daftar persiapan ditolak tanpa mengubah data.',
            actor: $actor,
            after: ['failure_code' => $failureCode],
        );
    }

    /** @return array<string, mixed> */
    private function academicYearSnapshot(AcademicYear $academicYear): array
    {
        return [
            'name' => $academicYear->name,
            'starts_on' => $academicYear->starts_on?->toDateString(),
            'ends_on' => $academicYear->ends_on?->toDateString(),
            'is_active' => $academicYear->is_active,
            'master_source' => $academicYear->master_source,
            'prepared_by' => $academicYear->prepared_by,
            'preparation_reference' => $academicYear->preparation_reference,
            'activated_by' => $academicYear->activated_by,
            'activated_at' => $academicYear->activated_at?->toIso8601String(),
        ];
    }
}
