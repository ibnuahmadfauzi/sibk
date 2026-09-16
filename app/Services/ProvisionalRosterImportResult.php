<?php

declare(strict_types=1);

namespace App\Services;

final readonly class ProvisionalRosterImportResult
{
    public function __construct(
        public int $rows,
        public int $studentsCreated,
        public int $studentsMatched,
        public int $classroomsCreated,
        public int $membershipsCreated,
        public int $membershipsUnchanged,
    ) {}
}
