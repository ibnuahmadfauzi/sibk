<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\Consultation;
use App\Models\Student;
use App\Models\TeacherAssignment;
use App\Models\User;
use Database\Seeders\DummyCaseAndServiceSeeder;
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
        $classes = $fajar->classMemberships()
            ->with('classroom')
            ->orderBy('effective_from')
            ->get()
            ->pluck('classroom.name')
            ->all();

        $this->assertSame(['X-RPL-1', 'XI-RPL-1'], $classes);
        $this->assertFalse($previousYear->is_active);
        $this->assertSame(6, TeacherAssignment::query()->where('academic_year_id', $currentYear->id)->count());
        $this->assertSame(17, BkCase::query()->count());
        $this->assertSame(2, BkCase::query()->whereDate('service_date', '<=', $previousYear->ends_on)->count());

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
}
