<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\StudentClassMembership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class WakaMonitoringService
{
    /** @var list<string> */
    private const array VIEW_MODES = [
        'dashboard',
        'students',
        'reports.penanganan',
        'reports.rekap',
        'reports.laporan-akhir',
    ];

    public function __construct(private readonly WakaCaseProjectionQuery $projectionQuery) {}

    /**
     * @param  array<string, string|null>  $filters
     * @return LengthAwarePaginator<array<string, mixed>>
     */
    public function paginateSafe(User $waka, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $paginator = $this->projectionQuery
            ->build($waka, $filters)
            ->paginate($perPage, ['*'], 'page', (int) ($filters['page'] ?? 1));

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (BkCase $case): array => $this->toSafeRow($case)),
        );

        return $paginator;
    }

    /**
     * @param  array<string, string|null>  $filters
     * @return Collection<int, array<string, string>>
     */
    public function exportCsvRows(User $waka, array $filters): Collection
    {
        return $this->projectionQuery
            ->build($waka, $filters)
            ->get()
            ->map(fn (BkCase $case): array => $this->toCsvRow($case));
    }

    /** @param array<string, string|null> $filters */
    public function auditViewed(
        User $actor,
        string $mode,
        array $filters,
        int $resultCount,
        Request $request,
    ): void {
        if (! in_array($mode, self::VIEW_MODES, true)) {
            throw new InvalidArgumentException('Mode audit portal Waka tidak valid.');
        }

        AuditLog::query()->create([
            'actor_id' => $actor->getKey(),
            'action' => 'waka.monitoring.viewed',
            'auditable_type' => 'waka_monitoring',
            'auditable_id' => $actor->getKey(),
            'summary' => sprintf(
                'Waka membaca portal monitoring. Mode: %s, Periode: %s, Status: %s, Urutan: %s %s, Halaman: %s, Hasil: %d baris.',
                $mode,
                $filters['period'] ?? 'semua',
                $filters['status'] ?? 'semua',
                $filters['sort'] ?? 'tanggal',
                $filters['direction'] ?? 'desc',
                $filters['page'] ?? '1',
                $resultCount,
            ),
            'before_values' => null,
            'after_values' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /** @param array<string, string|null> $filters */
    public function auditExported(User $actor, array $filters, int $rowCount, string $format, Request $request): void
    {
        AuditLog::query()->create([
            'actor_id' => $actor->getKey(),
            'action' => 'waka.monitoring.exported',
            'auditable_type' => 'waka_monitoring',
            'auditable_id' => $actor->getKey(),
            'summary' => sprintf(
                'Waka mengekspor data monitoring. Periode: %s, Status: %s, Format: %s, Jumlah baris: %d.',
                $filters['period'] ?? 'semua',
                $filters['status'] ?? 'semua',
                $format,
                $rowCount,
            ),
            'before_values' => null,
            'after_values' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /** @return array<string, mixed> */
    public function toSafeRow(BkCase $case): array
    {
        $membership = $case->student?->classMemberships->first(
            static fn (StudentClassMembership $item): bool => $item->effective_from?->lte($case->service_date)
                && ($item->effectiveEnd() === null || $item->effectiveEnd()->gte($case->service_date)),
        );
        $ownerAssignment = $case->assignments->first(
            static fn (CaseAssignment $assignment): bool => $assignment->effective_from?->lte(today())
                && ($assignment->effective_until === null || $assignment->effective_until->gte(today())),
        ) ?? $case->assignments->first();
        $owner = $ownerAssignment?->teacher;
        $nextFollowUp = $case->followUps->first();

        return [
            'nama_murid' => $case->identityName(),
            'kelas' => $membership?->classroom?->name ?? '-',
            'bidang' => $case->serviceField?->label ?? '-',
            'status' => $case->status?->label ?? '-',
            'status_code' => $case->status?->code ?? '',
            'guru_bk' => $owner?->name ?? '-',
            'tanggal' => $case->service_date?->locale('id')->translatedFormat('d M Y') ?? '-',
            'waka_summary' => $case->waka_summary,
            'tindak_lanjut' => $nextFollowUp ? [
                'jenis' => $nextFollowUp->type?->label ?? '-',
                'tanggal' => $nextFollowUp->planned_date?->locale('id')->translatedFormat('d M Y') ?? '-',
            ] : null,
            'coordination_url' => $case->coordinations->isNotEmpty()
                ? route('cases.show', $case->getKey())
                : null,
            'is_terminal' => $case->closed_at !== null,
        ];
    }

    /** @return array<string, string> */
    private function toCsvRow(BkCase $case): array
    {
        $row = $this->toSafeRow($case);
        $followUp = $row['tindak_lanjut'];

        return collect([
            'Murid' => $row['nama_murid'],
            'Kelas' => $row['kelas'],
            'Bidang Layanan' => $row['bidang'],
            'Status' => $row['status'],
            'Guru BK' => $row['guru_bk'],
            'Tanggal Pelayanan' => $row['tanggal'],
            'Ringkasan Waka' => $row['waka_summary'] ?? '',
            'Jenis Tindak Lanjut' => $followUp['jenis'] ?? '',
            'Tgl Tindak Lanjut' => $followUp['tanggal'] ?? '',
        ])->map(static fn (mixed $value): string => self::escapeCsvFormula((string) $value))->all();
    }

    private static function escapeCsvFormula(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/u', $value) === 1 ? "'{$value}" : $value;
    }
}
