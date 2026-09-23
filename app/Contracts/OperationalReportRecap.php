<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\BkCase;
use App\Models\Consultation;
use App\Models\User;

interface OperationalReportRecap
{
    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function paginateForUi(User $actor, array $filters): array;

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function allForDocument(User $actor, array $filters): array;

    public function findRecord(User $actor, string $type, int $id): BkCase|Consultation;

    /** @return array<string, mixed> */
    public function recordForDocument(BkCase|Consultation $record): array;
}
