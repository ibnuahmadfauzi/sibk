<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Requests\WakaMonitoringRequest;
use App\Models\AuditLog;
use App\Models\BkCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Menyediakan proyeksi aman seluruh kasus sekolah untuk Waka Kesiswaan.
 *
 * PENTING: Service ini TIDAK menggunakan BkCase::accessibleTo() yang berbasis assignment/koordinasi.
 * Waka dapat melihat seluruh kasus aktif sekolah, tetapi hanya melalui field aman yang didefinisikan
 * dalam allowlist. Model BkCase mentah tidak dikirim ke view maupun controller.
 */
class WakaMonitoringService
{
    /**
     * Mapping sort key (dari request) ke kolom SQL aktual.
     * Nilai request tidak boleh dipakai langsung sebagai nama kolom.
     *
     * @var array<string, string>
     */
    private const array SORT_MAP = [
        'murid'    => 'students.name',
        'kelas'    => 'classrooms.name',
        'bidang'   => 'bidang.label',
        'status'   => 'status_ref.label',
        'guru_bk'  => 'users.name',
        'tanggal'  => 'cases.service_date',
    ];

    /**
     * Ambil data paginasi proyeksi aman untuk halaman monitoring.
     *
     * @param  array<string, string|null>  $normalizedParams
     * @return LengthAwarePaginator<array<string, mixed>>
     */
    public function paginate(array $normalizedParams, int $perPage = 20): LengthAwarePaginator
    {
        return $this->baseQuery($normalizedParams)
            ->paginate($perPage, ['*'], 'page', (int) ($normalizedParams['page'] ?? 1));
    }

    /**
     * Ambil seluruh data proyeksi aman untuk ekspor CSV.
     *
     * @param  array<string, string|null>  $normalizedParams
     * @return Collection<int, array<string, mixed>>
     */
    public function export(array $normalizedParams): Collection
    {
        return $this->baseQuery($normalizedParams)->get();
    }

    /**
     * Query aman: hanya select field yang diizinkan, tidak termasuk NISN, registration_number,
     * initial_info, internal_note, catatan konseling, atau narasi sensitif lainnya.
     *
     * @param  array<string, string|null>  $normalizedParams
     * @return Builder<BkCase>
     */
    private function baseQuery(array $normalizedParams): Builder
    {
        $sortColumn   = self::SORT_MAP[$normalizedParams['sort'] ?? 'tanggal'] ?? 'cases.service_date';
        $sortDirection = ($normalizedParams['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query = BkCase::query()
            ->select([
                'cases.id',
                'cases.service_date',
                'cases.waka_summary',
                'cases.closed_at',
                'cases.student_id',
                'cases.temporary_student_id',
            ])
            ->with([
                // Nama murid (tanpa NISN — tidak di-select di sini)
                'student:id,name',
                'temporaryStudent:id,input_name',
                // Kelas historis saat tanggal layanan
                'student.classMemberships' => static function ($q): void {
                    $q->with('classroom:id,name')->orderByDesc('effective_from');
                },
                // Field aman: bidang layanan dan status
                'serviceField:id,label',
                'status:id,label,code',
                // Guru BK penanggung jawab aktif (owner assignment)
                'assignments' => static function ($q): void {
                    $q->where('assignment_type', 'owner')
                        ->effectiveOn(now())
                        ->with('user:id,name')
                        ->limit(1);
                },
                // Tindak lanjut berikutnya: hanya jenis dan tanggal
                'followUps' => static function ($q): void {
                    $q->whereHas('status', fn (Builder $s): Builder => $s->where('code', '!=', 'dibatalkan'))
                        ->where('planned_date', '>=', today())
                        ->orderBy('planned_date')
                        ->with('type:id,label')
                        ->limit(1);
                },
            ])
            ->when($normalizedParams['period'] ?? null, static function (Builder $q, string $period): Builder {
                [$year, $month] = explode('-', $period);
                return $q->whereYear('cases.service_date', $year)
                    ->whereMonth('cases.service_date', $month);
            })
            ->when($normalizedParams['status'] ?? null, static function (Builder $q, string $statusCode): Builder {
                return $q->whereHas('status', fn (Builder $s): Builder => $s->where('code', $statusCode));
            })
            ->orderBy($sortColumn, $sortDirection);

        return $query;
    }

    /**
     * Catat event audit pembacaan portal Waka.
     * Audit tidak menyimpan identitas murid, kode kasus, waka_summary, atau narasi sensitif.
     *
     * @param  array<string, string|null>  $normalizedParams
     */
    public function auditViewed(User $actor, array $normalizedParams, int $resultCount, Request $request): void
    {
        AuditLog::query()->create([
            'actor_id'       => $actor->getKey(),
            'action'         => 'waka.monitoring.viewed',
            'auditable_type' => 'waka_monitoring',
            'auditable_id'   => $actor->getKey(),
            'summary'        => sprintf(
                'Waka membaca portal monitoring. Periode: %s, Status: %s, Urutan: %s %s, Halaman: %s, Hasil: %d baris.',
                $normalizedParams['period'] ?? 'semua',
                $normalizedParams['status'] ?? 'semua',
                $normalizedParams['sort'] ?? 'tanggal',
                $normalizedParams['direction'] ?? 'desc',
                $normalizedParams['page'] ?? '1',
                $resultCount,
            ),
            'before_values'  => null,
            'after_values'   => null,
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);
    }

    /**
     * Catat event audit ekspor portal Waka.
     * Audit tidak menyimpan identitas murid, kode kasus, waka_summary, atau narasi sensitif.
     *
     * @param  array<string, string|null>  $normalizedParams
     */
    public function auditExported(User $actor, array $normalizedParams, int $rowCount, string $format, Request $request): void
    {
        AuditLog::query()->create([
            'actor_id'       => $actor->getKey(),
            'action'         => 'waka.monitoring.exported',
            'auditable_type' => 'waka_monitoring',
            'auditable_id'   => $actor->getKey(),
            'summary'        => sprintf(
                'Waka mengekspor data monitoring. Periode: %s, Status: %s, Format: %s, Jumlah baris: %d.',
                $normalizedParams['period'] ?? 'semua',
                $normalizedParams['status'] ?? 'semua',
                $format,
                $rowCount,
            ),
            'before_values'  => null,
            'after_values'   => null,
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);
    }

    /**
     * Transform baris query ke array field aman untuk dikirim ke view atau CSV.
     * Fungsi ini memastikan tidak ada field sensitif yang bocor.
     *
     * @return array<string, mixed>
     */
    public function toSafeRow(BkCase $case): array
    {
        $membership   = $case->student?->classMemberships->first();
        $activeOwner  = $case->assignments->first()?->user;
        $nextFollowUp = $case->followUps->first();

        return [
            'nama_murid'      => $case->student?->name ?? $case->temporaryStudent?->input_name ?? 'Identitas tidak tersedia',
            'kelas'           => $membership?->classroom?->name ?? '—',
            'bidang'          => $case->serviceField?->label ?? '—',
            'status'          => $case->status?->label ?? '—',
            'status_code'     => $case->status?->code ?? '',
            'guru_bk'         => $activeOwner?->name ?? '—',
            'tanggal'         => $case->service_date?->locale('id')->translatedFormat('d M Y') ?? '—',
            'waka_summary'    => $case->waka_summary,
            'tindak_lanjut'   => $nextFollowUp ? [
                'jenis'   => $nextFollowUp->type?->label ?? '—',
                'tanggal' => $nextFollowUp->planned_date?->locale('id')->translatedFormat('d M Y') ?? '—',
            ] : null,
            'is_terminal'     => $case->closed_at !== null,
        ];
    }

    /**
     * Baris CSV aman — tidak memuat registration_number, NISN, atau narasi sensitif.
     *
     * @return array<string, string>
     */
    public function toCsvRow(BkCase $case): array
    {
        $row          = $this->toSafeRow($case);
        $followUp     = $row['tindak_lanjut'];

        return [
            'Murid'             => $row['nama_murid'],
            'Kelas'             => $row['kelas'],
            'Bidang Layanan'    => $row['bidang'],
            'Status'            => $row['status'],
            'Guru BK'           => $row['guru_bk'],
            'Tanggal Pelayanan' => $row['tanggal'],
            'Ringkasan Waka'    => $row['waka_summary'] ?? '',
            'Jenis Tindak Lanjut' => $followUp['jenis'] ?? '',
            'Tgl Tindak Lanjut' => $followUp['tanggal'] ?? '',
        ];
    }
}
