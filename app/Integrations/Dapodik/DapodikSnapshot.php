<?php

declare(strict_types=1);

namespace App\Integrations\Dapodik;

final readonly class DapodikSnapshot
{
    /**
     * @param  list<array{source_id: string, name: string, starts_on?: string|null, ends_on?: string|null, is_active?: bool}>  $academicYears
     * @param  list<array{source_id: string, academic_year_source_id: string, name: string, grade_level?: int|null, major?: string|null, is_active?: bool}>  $classrooms
     * @param  list<array{source_id: string, nisn: string, name: string, is_active?: bool}>  $students
     * @param  list<array{source_id: string, student_source_id: string, classroom_source_id: string, academic_year_source_id: string, effective_from: string, effective_until?: string|null, is_active?: bool}>  $memberships
     */
    public function __construct(
        public bool $isFullSnapshot,
        public array $academicYears,
        public array $classrooms,
        public array $students,
        public array $memberships,
    ) {}

    public function recordCount(): int
    {
        return count($this->academicYears)
            + count($this->classrooms)
            + count($this->students)
            + count($this->memberships);
    }
}
