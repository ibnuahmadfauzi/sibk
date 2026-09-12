<?php

declare(strict_types=1);

namespace App\Services;

final readonly class AcademicYearRolloverSummary
{
    /**
     * @param list<array{
     *     student_id: int,
     *     nisn: string,
     *     student_name: string,
     *     source_classroom: string
     * }> $needsConfirmation
     */
    public function __construct(
        public int $targetYearId,
        public ?int $sourceYearId,
        public ?string $sourceYearName,
        public array $needsConfirmation,
    ) {}

    public function needsConfirmationCount(): int
    {
        return count($this->needsConfirmation);
    }
}
