<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;

interface OperationalReportRecap
{
    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function build(User $actor, array $filters): array;

    /** @param array<string, mixed> $filters @return array{id: string, columns: list<string>, rows: iterable<int, array<string, mixed>>} */
    public function exportRows(User $actor, array $filters): array;
}
