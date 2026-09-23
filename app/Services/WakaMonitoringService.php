<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\StudentClassMembership;
use App\Models\User;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class WakaMonitoringService
{
    /** @var list<string> */
    private const array VIEW_MODES = [
        'dashboard',
        'students',
        'reports.layanan',
    ];

    public function __construct(private readonly WakaCaseProjectionQuery $projectionQuery) {}

    /** @return array<string, mixed> */
    public function detailSafe(User $waka, int $caseId): array
    {
        $case = $this->projectionQuery->detail($waka, $caseId);

        return [
            ...$this->toSafeRow($case),
            'initial_info' => $case->initial_info,
            'initial_action' => $case->initial_action,
            'resolution_summary' => $case->resolution_summary,
        ];
    }

    /** @param array<string, mixed> $filters */
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

        $safeFilters = $this->safeAuditFilters($mode, $filters);

        AuditLog::query()->create([
            'actor_id' => $actor->getKey(),
            'action' => 'waka.monitoring.viewed',
            'auditable_type' => 'waka_monitoring',
            'auditable_id' => $actor->getKey(),
            'summary' => sprintf(
                'Waka membaca portal monitoring. Mode: %s, Parameter: %s, Hasil: %d baris.',
                $mode,
                json_encode($safeFilters, JSON_THROW_ON_ERROR),
                $resultCount,
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

        return [
            'nama_murid' => $case->identityName(),
            'kelas' => $membership?->classroom?->name ?? '-',
            'bidang' => $case->serviceField?->label ?? '-',
            'status' => $case->status?->label ?? '-',
            'status_code' => $case->status?->code ?? '',
            'guru_bk' => $owner?->name ?? '-',
            'tanggal' => $case->service_date?->locale('id')->translatedFormat('d M Y') ?? '-',
            'tindak_lanjut' => $case->followUpType?->label ?? '-',
            'detail_url' => route('cases.show', $case->getKey()),
            'is_terminal' => $case->closed_at !== null,
        ];
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    private function safeAuditFilters(string $mode, array $filters): array
    {
        $keys = match ($mode) {
            'dashboard' => ['academic_year_id'],
            'students' => ['period', 'status', 'sort', 'direction', 'page'],
            'reports.layanan' => [
                'academic_year_id',
                'classroom_id',
                'service_type',
                'per_page',
                'page',
            ],
        };

        return collect($filters)->only($keys)->all();
    }
}
