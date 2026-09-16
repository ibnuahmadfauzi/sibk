<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class ReportService
{
    public const TYPE_STUDENT_VIOLATIONS = 'pelanggaran-murid';

    public const TYPE_CLASS_VIOLATIONS = 'pelanggaran-kelas';

    public const TYPE_VIOLATION_POINTS = 'poin-pelanggaran';

    public const TYPE_CONSULTATIONS = 'konsultasi';

    public const TYPE_FOLLOW_UPS = 'status-tindak-lanjut';

    public const TYPE_SERVICE_RECAP = 'rekap-layanan-bk';

    public const TYPE_ACHIEVEMENTS = 'prestasi';

    public function __construct(private readonly LegacyReportAdapter $legacy) {}

    /** @return list<string> */
    public static function types(): array
    {
        return [
            self::TYPE_STUDENT_VIOLATIONS,
            self::TYPE_CLASS_VIOLATIONS,
            self::TYPE_VIOLATION_POINTS,
            self::TYPE_CONSULTATIONS,
            self::TYPE_FOLLOW_UPS,
            self::TYPE_SERVICE_RECAP,
            self::TYPE_ACHIEVEMENTS,
        ];
    }

    /** @return list<array<string, mixed>> */
    public function catalogFor(User $user): array
    {
        return $this->legacy->catalogFor($user);
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function build(User $user, array $filters, bool $paginate = true): array
    {
        return $this->legacy->build($user, $filters, $paginate);
    }

    /** @param array<string, mixed> $filters @return array{id: string, columns: list<string>, rows: iterable<int, array<string, mixed>>} */
    public function exportRows(User $user, array $filters): array
    {
        return $this->legacy->exportRows($user, $filters);
    }
}
