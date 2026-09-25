<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\AuditLog;
use App\Models\BkCase;
use App\Models\Classroom;
use App\Models\ClassroomCatalog;
use App\Models\Consultation;
use App\Models\DapodikSyncPreviewItem;
use App\Models\EtatibIdentityMapping;
use App\Models\ExternalSyncIssue;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\StudentDeparture;
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
        private readonly ProvisionalRosterPayloadParser $payloadParser,
        private readonly AuditService $auditService,
        private readonly AcademicYearRolloverQuery $rolloverQuery,
    ) {}

    /** @param array<string, mixed> $data */
    public function prepareAcademicYear(array $data, User $actor): AcademicYear
    {
        Gate::forUser($actor)->authorize('manageDataMaster');

        $normalized = [
            'name' => trim((string) ($data['name'] ?? '')),
        ];
        $validated = Validator::make($normalized, [
            'name' => ['required', 'string', 'max:20', 'regex:/^\d{4}\/\d{4}$/D'],
        ])->validate();

        [$firstYear, $lastYear] = array_map('intval', explode('/', $validated['name']));
        if ($lastYear !== $firstYear + 1) {
            throw ValidationException::withMessages([
                'name' => 'Tahun ajaran harus terdiri dari dua tahun berurutan.',
            ]);
        }

        return DB::transaction(function () use ($validated, $actor, $firstYear, $lastYear): AcademicYear {
            if (AcademicYear::query()->where('name', $validated['name'])->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['name' => 'Nama tahun ajaran sudah digunakan.']);
            }

            $year = AcademicYear::query()->create([
                ...$validated,
                'starts_on' => sprintf('%04d-07-01', $firstYear),
                'ends_on' => sprintf('%04d-06-30', $lastYear),
                'is_active' => false,
                'master_source' => AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
                'source_confirmed_at' => null,
                'prepared_by' => $actor->getKey(),
            ]);

            foreach (ClassroomCatalog::query()->where('is_active', true)->orderBy('name')->get() as $catalog) {
                Classroom::query()->create([
                    'academic_year_id' => $year->id,
                    'classroom_catalog_id' => $catalog->id,
                    'name' => $catalog->name,
                    'is_active' => true,
                    'master_source' => Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL,
                ]);
            }

            $this->auditService->record(
                action: 'academic_year.prepared',
                auditable: $year,
                summary: 'Tahun ajaran persiapan dibuat oleh Admin IT.',
                actor: $actor,
                after: $this->academicYearSnapshot($year),
            );

            return $year;
        });
    }

    public function cancelPreparationYear(AcademicYear $academicYear, User $actor): void
    {
        Gate::forUser($actor)->authorize('manageDataMaster');

        DB::transaction(function () use ($academicYear, $actor): void {
            $year = AcademicYear::query()->lockForUpdate()->findOrFail($academicYear->getKey());
            if ($year->is_active
                || $year->activated_at !== null
                || $year->master_source !== AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL) {
                throw ValidationException::withMessages([
                    'academic_year' => 'Hanya tahun Persiapan yang belum pernah aktif dapat dibatalkan.',
                ]);
            }

            $classroomIds = $year->classrooms()->pluck('id');
            $hasServices = BkCase::query()->withTrashed()
                ->where(fn ($query) => $query->where('academic_year_id', $year->getKey())
                    ->orWhereIn('classroom_id', $classroomIds))->exists()
                || Consultation::query()->withTrashed()
                    ->where(fn ($query) => $query->where('academic_year_id', $year->getKey())
                        ->orWhereIn('classroom_id', $classroomIds))->exists();
            $hasOfficialData = $year->classrooms()
                ->where(fn ($query) => $query->where('master_source', '!=', Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL)
                    ->orWhereNull('master_source'))->exists()
                || $year->studentClassMemberships()
                    ->where(fn ($query) => $query->where('master_source', '!=', StudentClassMembership::MASTER_SOURCE_SCHOOL_PROVISIONAL)
                        ->orWhereNull('master_source'))->exists();
            if ($hasServices || $hasOfficialData) {
                throw ValidationException::withMessages([
                    'academic_year' => 'Persiapan tidak dapat dibatalkan karena sudah memiliki layanan atau data terverifikasi.',
                ]);
            }

            $studentIds = $year->studentClassMemberships()->distinct()->pluck('student_id');
            $classroomsDeleted = $classroomIds->count();
            $membershipsDeleted = $year->studentClassMemberships()->count();
            $assignmentsDeleted = $year->teacherAssignments()->count();

            $year->teacherAssignments()->delete();
            $year->studentClassMemberships()->delete();
            $year->classrooms()->delete();
            $year->delete();

            $orphanStudentIds = Student::query()
                ->whereIn('id', $studentIds)
                ->where('master_source', Student::MASTER_SOURCE_SCHOOL_PROVISIONAL)
                ->whereNull('dapodik_id')
                ->whereNull('source_confirmed_at')
                ->whereIn('id', AuditLog::query()
                    ->where('action', 'provisional_student.created')
                    ->where('auditable_type', (new Student)->getMorphClass())
                    ->select('auditable_id'))
                ->whereDoesntHave('classMemberships')
                ->whereDoesntHave('temporaryIdentities')
                ->whereDoesntHave('cases', fn ($query) => $query->withTrashed())
                ->whereDoesntHave('consultations', fn ($query) => $query->withTrashed())
                ->whereDoesntHave('achievements')
                ->whereDoesntHave('etatibRecords')
                ->whereDoesntHave('departure')
                ->whereNotIn('id', EtatibIdentityMapping::query()->select('student_id'))
                ->whereNotIn('id', ExternalSyncIssue::query()->whereNotNull('resolved_student_id')->select('resolved_student_id'))
                ->whereNotIn('id', DapodikSyncPreviewItem::query()
                    ->where('entity_type', DapodikSyncPreviewItem::ENTITY_STUDENT)
                    ->whereNotNull('candidate_id')->select('candidate_id'))
                ->whereNotIn('id', DapodikSyncPreviewItem::query()
                    ->where('entity_type', DapodikSyncPreviewItem::ENTITY_STUDENT)
                    ->whereNotNull('decision_candidate_id')->select('decision_candidate_id'))
                ->pluck('id');
            Student::query()->whereIn('id', $orphanStudentIds)->delete();

            $this->auditService->record(
                action: 'academic_year.preparation_deleted',
                auditable: $year,
                summary: 'Persiapan tahun ajaran dibatalkan.',
                actor: $actor,
                before: $this->academicYearSnapshot($year),
                after: [
                    'classrooms_deleted' => $classroomsDeleted,
                    'memberships_deleted' => $membershipsDeleted,
                    'assignments_deleted' => $assignmentsDeleted,
                    'students_deleted' => $orphanStudentIds->count(),
                ],
            );
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
            if (collect($rows)->contains(
                fn (array $row): bool => $row['academic_year_name'] !== $currentYear->name,
            )) {
                throw ValidationException::withMessages([
                    'academic_year' => 'Tahun pelajaran pada CSV harus sama dengan tahun ajaran tujuan.',
                ]);
            }

            return DB::transaction(function () use ($academicYear, $rows, $actor): ProvisionalRosterImportResult {
                $year = AcademicYear::query()->lockForUpdate()->findOrFail($academicYear->getKey());
                $this->assertImportable($year);

                return $this->processRosterRows($rows, new Collection([$year])->keyBy('name'), $actor);
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

    public function importRosters(UploadedFile $file, User $actor): ProvisionalRosterImportResult
    {
        Gate::forUser($actor)->authorize('manageDataMaster');

        try {
            $rows = $this->csvParser->parse($file);

            return $this->importRosterRows($rows, $actor, 'CSV');
        } catch (ValidationException $exception) {
            $this->recordGlobalRosterImportFailure($actor, $this->safeFailureCode($exception));

            throw $exception;
        } catch (Throwable $exception) {
            $this->recordGlobalRosterImportFailure($actor, 'processing_failed');

            throw $exception;
        }
    }

    /** @param array<string, mixed> $payload */
    public function importRosterPayload(array $payload, User $actor): ProvisionalRosterImportResult
    {
        Gate::forUser($actor)->authorize('manageDataMaster');

        try {
            $rows = $this->payloadParser->parse($payload);

            return $this->importRosterRows($rows, $actor, 'API');
        } catch (ValidationException $exception) {
            $this->recordGlobalRosterImportFailure($actor, $this->safeFailureCode($exception));

            throw $exception;
        } catch (Throwable $exception) {
            $this->recordGlobalRosterImportFailure($actor, 'processing_failed');

            throw $exception;
        }
    }

    /**
     * @param  list<array{nisn: string, name: string, classroom: string, academic_year_name: string}>  $rows
     */
    private function importRosterRows(array $rows, User $actor, string $sourceLabel): ProvisionalRosterImportResult
    {
        return DB::transaction(function () use ($rows, $actor, $sourceLabel): ProvisionalRosterImportResult {
            $yearNames = collect($rows)->pluck('academic_year_name')->unique()->values();
            $years = AcademicYear::query()
                ->whereIn('name', $yearNames)
                ->lockForUpdate()
                ->get();

            if ($years->count() !== $yearNames->count()) {
                throw ValidationException::withMessages([
                    'academic_year' => "Semua tahun pelajaran pada {$sourceLabel} harus sudah dibuat di Data Master.",
                ]);
            }

            foreach ($years as $year) {
                $this->assertImportable($year);
            }

            return $this->processRosterRows($rows, $years->keyBy('name'), $actor);
        });
    }

    /**
     * @param  list<array{nisn: string, name: string, classroom: string, academic_year_name: string}>  $rows
     * @param  Collection<string, AcademicYear>  $yearsByName
     */
    private function processRosterRows(array $rows, Collection $yearsByName, User $actor): ProvisionalRosterImportResult
    {
        /** @var array<string, Student|null> $studentsByNisn */
        $studentsByNisn = [];
        foreach ($rows as $row) {
            if (! array_key_exists($row['nisn'], $studentsByNisn)) {
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

                $studentsByNisn[$row['nisn']] = $students->first();
            }

            $student = $studentsByNisn[$row['nisn']];
            $year = $yearsByName->get($row['academic_year_name']);
            if (! $year instanceof AcademicYear) {
                throw ValidationException::withMessages([
                    'academic_year' => 'Tahun pelajaran pada CSV tidak ditemukan.',
                ]);
            }
            if ($student !== null) {
                $this->assertMembershipMatchesRoster($student, $year, $row['classroom']);
            }
        }

        $studentsCreated = 0;
        $studentsMatched = 0;
        $classroomsCreated = 0;
        $membershipsCreated = 0;
        $membershipsUnchanged = 0;
        /** @var array<string, Classroom> $classroomsByYearAndName */
        $classroomsByYearAndName = [];
        $catalogsByName = [];

        foreach ($rows as $row) {
            /** @var AcademicYear $year */
            $year = $yearsByName->get($row['academic_year_name']);
            $classroomKey = $year->getKey().'|'.mb_strtolower($row['classroom']);
            $classroom = $classroomsByYearAndName[$classroomKey] ?? null;

            if ($classroom === null) {
                $catalogKey = mb_strtolower($row['classroom']);
                if (! array_key_exists($catalogKey, $catalogsByName)) {
                    $catalog = ClassroomCatalog::query()
                        ->whereRaw('LOWER(name) = ?', [$catalogKey])
                        ->lockForUpdate()
                        ->first();
                    if ($catalog?->is_active === false) {
                        throw ValidationException::withMessages([
                            'rombel' => "Rombel {$row['classroom']} nonaktif di Data Kelas. Aktifkan kembali sebelum impor.",
                        ]);
                    }
                    if ($catalog === null) {
                        $catalog = ClassroomCatalog::query()->create([
                            'name' => $row['classroom'],
                            'is_active' => true,
                        ]);
                        $this->auditService->record(
                            action: 'classroom_catalog.created', auditable: $catalog,
                            summary: 'Rombel baru dari daftar murid ditambahkan ke Data Kelas.', actor: $actor,
                            after: ['name' => $catalog->name, 'is_active' => true],
                        );
                    }
                    $catalogsByName[$catalogKey] = $catalog;
                }
                $catalog = $catalogsByName[$catalogKey];
                $matchingClassrooms = Classroom::query()
                    ->where('academic_year_id', $year->getKey())
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($row['classroom'])])
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
                        'classroom_catalog_id' => $catalog->id,
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
                } elseif ($classroom->classroom_catalog_id === null) {
                    $classroom->update(['classroom_catalog_id' => $catalog->id]);
                }
                $classroomsByYearAndName[$classroomKey] = $classroom;
            }

            $student = $studentsByNisn[$row['nisn']];
            if ($student === null) {
                $student = Student::query()->create([
                    'nisn' => $row['nisn'],
                    'name' => $row['name'],
                    'is_active' => true,
                    'master_source' => Student::MASTER_SOURCE_SCHOOL_PROVISIONAL,
                    'source_confirmed_at' => null,
                ]);
                $studentsByNisn[$row['nisn']] = $student;
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
                ->lockForUpdate()
                ->first();

            if ($membership === null) {
                $membership = StudentClassMembership::query()->create([
                    'student_id' => $student->getKey(),
                    'classroom_id' => $classroom->getKey(),
                    'academic_year_id' => $year->getKey(),
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
            academicYears: $yearsByName->count(),
        );

        /** @var AcademicYear $auditYear */
        $auditYear = $yearsByName->first();
        $this->auditService->record(
            action: 'academic_year.roster_imported',
            auditable: $auditYear,
            summary: 'Daftar persiapan murid diproses tanpa menyimpan berkas mentah.',
            actor: $actor,
            after: [
                'rows' => $result->rows,
                'academic_years' => $result->academicYears,
                'students_created' => $result->studentsCreated,
                'students_matched' => $result->studentsMatched,
                'classrooms_created' => $result->classroomsCreated,
                'memberships_created' => $result->membershipsCreated,
                'memberships_unchanged' => $result->membershipsUnchanged,
            ],
        );

        return $result;
    }

    public function activate(AcademicYear $academicYear, User $actor): AcademicYear
    {
        Gate::forUser($actor)->authorize('create', TeacherAssignment::class);

        return DB::transaction(function () use ($academicYear, $actor): AcademicYear {
            $year = AcademicYear::query()->lockForUpdate()->findOrFail($academicYear->getKey());
            if ($year->is_active) {
                throw ValidationException::withMessages(['academic_year' => 'Tahun ajaran sudah aktif.']);
            }
            if ($year->activated_at !== null) {
                throw ValidationException::withMessages(['academic_year' => 'Tahun ajaran arsip tidak dapat diaktifkan kembali.']);
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

    public function previousYearCandidate(AcademicYear $current): ?AcademicYear
    {
        if (! $current->is_active || $current->activated_at === null) {
            return null;
        }

        $candidates = AcademicYear::query()
            ->where('is_active', false)
            ->whereNotNull('activated_at')
            ->where('activated_at', '<', $current->activated_at)
            ->orderByDesc('activated_at')
            ->limit(2)
            ->get();

        $previous = $candidates->first();
        if ($previous === null || ($candidates->count() > 1
            && $candidates[1]->activated_at->equalTo($previous->activated_at))) {
            return null;
        }

        return $previous;
    }

    public function restorePreviousAcademicYear(AcademicYear $academicYear, User $actor): AcademicYear
    {
        Gate::forUser($actor)->authorize('create', TeacherAssignment::class);
        abort_unless($actor->is_active, 403);

        return DB::transaction(function () use ($academicYear, $actor): AcademicYear {
            $current = AcademicYear::query()->lockForUpdate()->findOrFail($academicYear->getKey());
            if (! $current->is_active || AcademicYear::query()->active()->count() !== 1) {
                throw ValidationException::withMessages(['academic_year' => 'Tahun ajaran aktif sudah berubah.']);
            }

            $candidate = $this->previousYearCandidate($current);
            if ($candidate === null) {
                throw ValidationException::withMessages(['academic_year' => 'Tahun ajaran sebelumnya tidak dapat dipastikan.']);
            }
            $previous = AcademicYear::query()->lockForUpdate()->findOrFail($candidate->getKey());
            $assessment = $this->activationStructure($previous, lockForUpdate: true);
            if ($assessment['blocking'] !== []) {
                throw ValidationException::withMessages([
                    'academic_year' => 'Tahun ajaran sebelumnya tidak lagi siap untuk digunakan.',
                ]);
            }

            $activatedAt = $current->activated_at;
            $hasActivity = BkCase::query()->withTrashed()
                ->where('academic_year_id', $current->getKey())
                ->orWhere('created_at', '>=', $activatedAt)
                ->orWhere('updated_at', '>=', $activatedAt)
                ->exists()
                || Consultation::query()->withTrashed()
                    ->where('academic_year_id', $current->getKey())
                    ->orWhere('created_at', '>=', $activatedAt)
                    ->orWhere('updated_at', '>=', $activatedAt)
                    ->exists()
                || Achievement::query()->withTrashed()
                    ->where('created_at', '>=', $activatedAt)
                    ->orWhere('updated_at', '>=', $activatedAt)
                    ->exists()
                || StudentDeparture::query()
                    ->where('created_at', '>=', $activatedAt)
                    ->orWhere('updated_at', '>=', $activatedAt)
                    ->exists();
            if ($hasActivity) {
                throw ValidationException::withMessages([
                    'academic_year' => 'Tahun ajaran tidak dapat dikembalikan karena sudah ada aktivitas operasional.',
                ]);
            }

            $currentBefore = $this->academicYearSnapshot($current);
            $previousBefore = $this->academicYearSnapshot($previous);
            $current->update(['is_active' => false]);
            $previous->update(['is_active' => true]);
            $this->auditService->record(
                action: 'academic_year.activation_reverted',
                auditable: $current,
                summary: 'Aktivasi tahun ajaran dikembalikan ke tahun sebelumnya.',
                actor: $actor,
                before: $currentBefore,
                after: $this->academicYearSnapshot($current->refresh()),
            );
            $this->auditService->record(
                action: 'academic_year.reactivated',
                auditable: $previous,
                summary: 'Tahun ajaran sebelumnya kembali digunakan.',
                actor: $actor,
                before: $previousBefore,
                after: $this->academicYearSnapshot($previous->refresh()),
            );

            return $previous;
        });
    }

    /**
     * @return array{
     *     ready: bool,
     *     state: 'active'|'archived'|'not_ready'|'ready',
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

        $state = match (true) {
            $academicYear->is_active => 'active',
            $academicYear->activated_at !== null => 'archived',
            $issues !== [] => 'not_ready',
            default => 'ready',
        };

        return [
            'ready' => $state === 'ready',
            'state' => $state,
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
                ->where('academic_year_id', $academicYear->getKey());
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
                'ready' => $assignmentReady,
            ];
        });

        $blocking = [];
        if ($classrooms->isEmpty()) {
            $blocking['classrooms'] = 'Belum ada rombel aktif pada tahun ajaran ini.';
        }
        if ($activeMemberships->isEmpty()) {
            $blocking['students'] = 'Minimal satu murid harus tersedia pada tahun ajaran ini.';
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
                'academic_year' => 'Daftar murid hanya dapat diimpor ke tahun ajaran sementara.',
            ]);
        }
        if (! $academicYear->is_active && $academicYear->activated_at !== null) {
            throw ValidationException::withMessages([
                'academic_year' => 'Tahun ajaran yang sudah selesai tidak dapat menerima impor murid.',
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
                'rombel' => sprintf(
                    'Rombel murid dengan NISN %s berbeda pada tahun ini. Periksa daftar sekolah.',
                    substr($student->nisn, 0, 4).'****'.substr($student->nisn, -2),
                ),
            ]);
        }
    }

    private function safeFailureCode(ValidationException $exception): string
    {
        return match (array_key_first($exception->errors())) {
            'file' => 'invalid_csv',
            'data' => 'invalid_api_payload',
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

    private function recordGlobalRosterImportFailure(User $actor, string $failureCode): void
    {
        $this->auditService->record(
            action: 'academic_year.roster_import_failed',
            auditable: $actor,
            summary: 'Impor daftar persiapan lintas tahun ditolak tanpa mengubah data.',
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
