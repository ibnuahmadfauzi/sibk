<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\Consultation;
use App\Models\Student;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\AcademicYearPreparationService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DummyCaseAndServiceSeeder;
use Database\Seeders\StudentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DummyCaseAndServiceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_consultation_dummy_data_can_be_seeded_fresh_and_rerun_idempotently(): void
    {
        $this->seed(DummyCaseAndServiceSeeder::class);

        $seededIds = Consultation::query()
            ->orderBy('session_date')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertCount(15, $seededIds);
        $this->assertSame(15, Consultation::query()
            ->whereNotNull('problem')
            ->whereNotNull('handling')
            ->whereNotNull('result')
            ->count());

        $this->seed(DummyCaseAndServiceSeeder::class);

        $this->assertSame($seededIds, Consultation::query()
            ->orderBy('session_date')
            ->orderBy('id')
            ->pluck('id')
            ->all());
        $this->assertDatabaseCount('consultations', 15);
    }

    public function test_demo_year_transition_and_case_owners_follow_class_assignments(): void
    {
        $this->seed(DummyCaseAndServiceSeeder::class);

        $currentYear = AcademicYear::query()->where('dapodik_id', 'SEED-ACADEMIC-YEAR')->firstOrFail();
        $previousYear = AcademicYear::query()->where('dapodik_id', 'SEED-ACADEMIC-YEAR-PREVIOUS')->firstOrFail();
        $fajar = Student::query()->where('nisn', '0091234506')->firstOrFail();
        $this->assertSame('X-RPL-1', $fajar->classMemberships()
            ->where('academic_year_id', $previousYear->id)->firstOrFail()->classroom->name);
        $this->assertSame('XI-RPL-1', $fajar->classMemberships()
            ->where('academic_year_id', $currentYear->id)->firstOrFail()->classroom->name);
        $this->assertFalse($previousYear->is_active);
        $this->assertSame(6, TeacherAssignment::query()->where('academic_year_id', $currentYear->id)->count());
        $this->assertSame(17, BkCase::query()->count());
        $this->assertSame(2, BkCase::query()->where('academic_year_id', $previousYear->id)->count());

        foreach (['guru.bk@ruangbk.test', 'guru.bk.rina@ruangbk.test', 'guru.bk.budi@ruangbk.test'] as $email) {
            $teacher = User::query()->where('email', $email)->firstOrFail();
            $this->assertSame(2, TeacherAssignment::query()
                ->where('academic_year_id', $currentYear->id)
                ->where('user_id', $teacher->id)
                ->count());
            $this->assertGreaterThan(0, BkCase::query()
                ->whereHas('assignments', fn ($assignments) => $assignments->where('user_id', $teacher->id))
                ->count());
        }
    }

    public function test_default_demo_seed_is_ready_for_manual_activation_and_preserves_it_on_rerun(): void
    {
        $this->seed(DatabaseSeeder::class);

        $year = AcademicYear::query()->where('dapodik_id', 'SEED-ACADEMIC-YEAR')->firstOrFail();
        $coordinator = User::query()->where('email', 'koordinator.bk@ruangbk.test')->firstOrFail();
        $this->assertFalse($year->is_active);
        $this->assertSame(6, TeacherAssignment::query()->where('academic_year_id', $year->id)->count());
        $this->assertSame(3, User::query()->whereHas('roles', fn ($roles) => $roles->where('slug', 'guru_bk'))->count());
        $this->assertTrue(app(AcademicYearPreparationService::class)->activationReadiness($year)['ready']);

        app(AcademicYearPreparationService::class)->activate($year, $coordinator);
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue($year->refresh()->is_active);
        $this->assertSame(6, TeacherAssignment::query()->where('academic_year_id', $year->id)->count());
    }

    public function test_old_auto_active_demo_year_returns_to_preparation_on_seed(): void
    {
        $this->seed(StudentSeeder::class);
        $year = AcademicYear::query()->where('dapodik_id', 'SEED-ACADEMIC-YEAR')->firstOrFail();
        $year->update(['is_active' => true, 'activated_at' => null]);

        $this->seed(DatabaseSeeder::class);

        $this->assertFalse($year->refresh()->is_active);
        $this->assertSame(6, TeacherAssignment::query()->where('academic_year_id', $year->id)->count());
    }

    public function test_rerunning_demo_seed_preserves_changed_teacher_and_existing_case_owner(): void
    {
        $this->seed(DatabaseSeeder::class);
        $year = AcademicYear::query()->where('dapodik_id', 'SEED-ACADEMIC-YEAR')->firstOrFail();
        $assignment = TeacherAssignment::query()
            ->where('academic_year_id', $year->id)
            ->whereHas('classroom', fn ($classrooms) => $classrooms->where('name', 'X-RPL-1'))
            ->firstOrFail();
        $case = BkCase::query()->where('classroom_id', $assignment->classroom_id)->firstOrFail();
        $originalOwner = $case->ownerAssignment()?->user_id;
        $nextTeacher = User::query()->where('email', 'guru.bk.budi@ruangbk.test')->firstOrFail();
        $assignment->update(['user_id' => $nextTeacher->id]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($nextTeacher->id, $assignment->fresh()->user_id);
        $this->assertSame($originalOwner, $case->fresh()->ownerAssignment()?->user_id);
    }
}
