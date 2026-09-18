<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\User;
use App\Support\ServiceRecordStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class WakaDashboardService
{
    public function __construct(
        private readonly WakaCaseProjectionQuery $projection,
        private readonly WakaMonitoringService $monitoring,
    ) {}

    /** @return array<string, mixed> */
    public function build(User $waka, ?AcademicYear $year): array
    {
        [$start, $end] = $this->period($year);
        /** @var Collection<int, BkCase> $cases */
        $cases = $this->projection->build($waka, [
            'date_start' => $start->toDateString(),
            'date_end' => $end->toDateString(),
        ])->get();

        return [
            'role_key' => 'waka',
            'label' => 'Waka Kesiswaan',
            'user_name' => $waka->name,
            'scope' => $year?->name ?? 'Periode kalender berjalan',
            'description' => 'Ringkasan kondisi layanan BK tingkat sekolah dari proyeksi aman seluruh kasus.',
            'read_only' => true,
            'metrics' => $this->metrics($cases),
            'attention' => $this->attentionRows($cases, 5),
            'status_composition' => $this->statusComposition($cases),
            'latest' => $this->latestRows($cases, 5),
        ];
    }

    /**
     * @param  Collection<int, BkCase>  $cases
     * @return list<array{label: string, value: string, meta: string, tone: string, kind: string}>
     */
    private function metrics(Collection $cases): array
    {
        $monthStart = today()->startOfMonth();
        $monthEnd = today()->endOfMonth();

        return [
            [
                'label' => 'Kasus berjalan',
                'value' => (string) $cases->filter(static fn (BkCase $case): bool => ! ServiceRecordStatus::isTerminal($case->status?->code))->count(),
                'meta' => 'Kasus nonterminal pada tahun ajaran',
                'tone' => 'primary',
                'kind' => 'cases',
            ],
            [
                'label' => 'Sedang diproses',
                'value' => (string) $cases->where('status.code', ServiceRecordStatus::IN_PROGRESS)->count(),
                'meta' => 'Penanganan aktif Guru BK',
                'tone' => 'info',
                'kind' => 'cases',
            ],
            [
                'label' => 'Membutuhkan tindak lanjut',
                'value' => (string) $cases->where('status.code', ServiceRecordStatus::NEEDS_FOLLOW_UP)->count(),
                'meta' => 'Perlu perhatian lanjutan',
                'tone' => 'warning',
                'kind' => 'schedule',
            ],
            [
                'label' => 'Selesai bulan ini',
                'value' => (string) $cases->filter(static fn (BkCase $case): bool => $case->status?->code === ServiceRecordStatus::COMPLETED
                    && $case->closed_at?->betweenIncluded($monthStart, $monthEnd))->count(),
                'meta' => 'Ditutup pada bulan berjalan',
                'tone' => 'success',
                'kind' => 'completed',
            ],
        ];
    }

    /**
     * @param  Collection<int, BkCase>  $cases
     * @return list<array<string, mixed>>
     */
    private function attentionRows(Collection $cases, int $limit): array
    {
        return $cases
            ->filter(static fn (BkCase $case): bool => $case->follow_up_type_id !== null
                || $case->status?->code === ServiceRecordStatus::NEEDS_FOLLOW_UP)
            ->sortByDesc(static fn (BkCase $case): string => sprintf(
                '%s-%020d',
                $case->service_date?->toDateString() ?? '',
                $case->getKey(),
            ))
            ->take($limit)
            ->map(fn (BkCase $case): array => $this->monitoring->toSafeRow($case))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, BkCase>  $cases
     * @return list<array{code: string, label: string, count: int}>
     */
    private function statusComposition(Collection $cases): array
    {
        return collect(ServiceRecordStatus::labels())->map(
            static fn (string $label, string $code): array => [
                'code' => $code,
                'label' => $label,
                'count' => $cases->where('status.code', $code)->count(),
            ],
        )->values()->all();
    }

    /**
     * @param  Collection<int, BkCase>  $cases
     * @return list<array<string, mixed>>
     */
    private function latestRows(Collection $cases, int $limit): array
    {
        return $cases
            ->sortByDesc(static fn (BkCase $case): string => sprintf(
                '%s-%020d',
                $case->service_date?->toDateString() ?? '',
                $case->getKey(),
            ))
            ->take($limit)
            ->map(fn (BkCase $case): array => $this->monitoring->toSafeRow($case))
            ->values()
            ->all();
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function period(?AcademicYear $year): array
    {
        return [
            $year?->starts_on !== null
                ? CarbonImmutable::instance($year->starts_on)->startOfDay()
                : CarbonImmutable::now()->startOfYear(),
            $year?->ends_on !== null
                ? CarbonImmutable::instance($year->ends_on)->endOfDay()
                : CarbonImmutable::now()->endOfYear(),
        ];
    }
}
