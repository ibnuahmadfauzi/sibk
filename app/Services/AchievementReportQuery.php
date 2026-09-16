<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class AchievementReportQuery extends LegacyReportQuery
{
    public function build(
        string $type,
        User $user,
        array $filters,
        ?AcademicYear $year,
        CarbonImmutable $start,
        CarbonImmutable $end,
        bool $paginate,
    ): array {
        abort_unless($type === ReportService::TYPE_ACHIEVEMENTS, 404);
        $data = $paginate
            ? $this->paginatedAchievements($user, $filters, $year, $start, $end)
            : $this->achievements($user, $filters, $year, $start, $end);
        $rows = $data['rows'];
        $data['rows'] = $rows instanceof LengthAwarePaginator
            ? $rows
            : ($paginate ? $this->paginate($rows, $filters) : $rows);

        return $data;
    }

    public function export(
        string $type,
        User $user,
        array $filters,
        ?AcademicYear $year,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): array {
        abort_unless($type === ReportService::TYPE_ACHIEVEMENTS, 404);

        return [
            'id' => $type,
            'columns' => ['Inisial Murid', 'NISN Tersamarkan', 'Kelas', 'Kegiatan', 'Jenis dan Tingkat', 'Hasil', 'Tanggal', 'Status Verifikasi'],
            'rows' => $this->achievementReportQuery($user, $filters, $year, $start, $end)
                ->latest('achievement_date')->latest('id')->lazy(500)
                ->map(fn (Achievement $achievement): array => $this->row([
                    $this->initials($achievement->student->name),
                    $this->maskNisn($achievement->student->nisn),
                    $this->historicClass($achievement->student, $achievement->achievement_date, $year),
                    $achievement->activity_name,
                    $achievement->type->label.' / '.$achievement->level->label,
                    $achievement->result,
                    $achievement->achievement_date->locale('id')->translatedFormat('d M Y'),
                    $achievement->verificationStatus->label,
                ], 7, $this->statusTone($achievement->verificationStatus->code))),
        ];
    }

    /** @return array{columns: list<string>, rows: Collection<int, array<string, mixed>>, stats: array<string, array<string, string>>} */
    protected function achievements(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $query = Achievement::query()->accessibleTo($user)
            ->whereBetween('achievement_date', [$start->toDateString(), $end->toDateString()])
            ->with(['student.classMemberships.classroom', 'type', 'level', 'verificationStatus']);
        $query->when($filters['student_id'] ?? null, fn (Builder $items, int $id): Builder => $items->where('student_id', $id));
        $query->when($filters['achievement_type_id'] ?? null, fn (Builder $items, int $id): Builder => $items->where('type_id', $id));
        $query->when($filters['achievement_level_id'] ?? null, fn (Builder $items, int $id): Builder => $items->where('level_id', $id));
        $query->when($filters['status_id'] ?? null, fn (Builder $items, int $id): Builder => $items->where('verification_status_id', $id));
        $this->applyHistoricClassFilter($query, $filters, $year, 'achievements.achievement_date', ['student.classMemberships']);
        $items = $query->get()->filter(fn (Achievement $achievement): bool => $this->matchesClass(
            $achievement->student,
            $achievement->achievement_date,
            $year,
            $filters,
        ));
        $rows = $items->sortByDesc('achievement_date')->values()->map(fn (Achievement $achievement): array => $this->row([
            $this->initials($achievement->student->name),
            $this->maskNisn($achievement->student->nisn),
            $this->historicClass($achievement->student, $achievement->achievement_date, $year),
            $achievement->activity_name,
            $achievement->type->label.' / '.$achievement->level->label,
            $achievement->result,
            $achievement->achievement_date->locale('id')->translatedFormat('d M Y'),
            $achievement->verificationStatus->label,
        ], 7, $this->statusTone($achievement->verificationStatus->code)));

        return [
            'columns' => ['Inisial Murid', 'NISN Tersamarkan', 'Kelas', 'Kegiatan', 'Jenis dan Tingkat', 'Hasil', 'Tanggal', 'Status Verifikasi'],
            'rows' => $rows,
            'stats' => $this->stats(
                ['Total Prestasi', $items->count(), 'Periode terpilih'],
                ['Terverifikasi', $items->where('verificationStatus.code', 'terverifikasi')->count(), 'Sudah diperiksa'],
                ['Menunggu Verifikasi', $items->where('verificationStatus.code', 'menunggu')->count(), 'Perlu pemeriksaan'],
            ),
        ];
    }

    /** @return array{columns: list<string>, rows: LengthAwarePaginator, stats: array<string, array<string, string>>} */
    protected function paginatedAchievements(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $query = $this->achievementReportQuery($user, $filters, $year, $start, $end);
        $total = (clone $query)->count();
        $verified = (clone $query)->whereHas('verificationStatus', fn (Builder $statuses): Builder => $statuses->where('code', 'terverifikasi'))->count();
        $pending = (clone $query)->whereHas('verificationStatus', fn (Builder $statuses): Builder => $statuses->where('code', 'menunggu'))->count();
        $page = $query->latest('achievement_date')->latest('id')->paginate(20)->withQueryString();
        $page->setCollection($page->getCollection()->map(fn (Achievement $achievement): array => $this->row([
            $this->initials($achievement->student->name),
            $this->maskNisn($achievement->student->nisn),
            $this->historicClass($achievement->student, $achievement->achievement_date, $year),
            $achievement->activity_name,
            $achievement->type->label.' / '.$achievement->level->label,
            $achievement->result,
            $achievement->achievement_date->locale('id')->translatedFormat('d M Y'),
            $achievement->verificationStatus->label,
        ], 7, $this->statusTone($achievement->verificationStatus->code))));

        return [
            'columns' => ['Inisial Murid', 'NISN Tersamarkan', 'Kelas', 'Kegiatan', 'Jenis dan Tingkat', 'Hasil', 'Tanggal', 'Status Verifikasi'],
            'rows' => $page,
            'stats' => $this->stats(
                ['Total Prestasi', $total, 'Periode terpilih'],
                ['Terverifikasi', $verified, 'Sudah diperiksa'],
                ['Menunggu Verifikasi', $pending, 'Perlu pemeriksaan'],
            ),
        ];
    }

    /** @return Builder<Achievement> */
    protected function achievementReportQuery(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        $query = Achievement::query()->accessibleTo($user)
            ->whereBetween('achievement_date', [$start->toDateString(), $end->toDateString()])
            ->with(['student.classMemberships.classroom', 'type', 'level', 'verificationStatus']);
        $query->when($filters['student_id'] ?? null, fn (Builder $items, int $id): Builder => $items->where('student_id', $id));
        $query->when($filters['achievement_type_id'] ?? null, fn (Builder $items, int $id): Builder => $items->where('type_id', $id));
        $query->when($filters['achievement_level_id'] ?? null, fn (Builder $items, int $id): Builder => $items->where('level_id', $id));
        $query->when($filters['status_id'] ?? null, fn (Builder $items, int $id): Builder => $items->where('verification_status_id', $id));
        $this->applyHistoricClassFilter($query, $filters, $year, 'achievements.achievement_date', ['student.classMemberships']);

        return $query;
    }
}
