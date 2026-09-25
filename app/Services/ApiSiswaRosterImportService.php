<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\ExternalSyncRun;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use JsonException;
use Psr\Http\Message\StreamInterface;
use Throwable;

final class ApiSiswaRosterImportService
{
    private const MAX_RESPONSE_BYTES = 2 * 1024 * 1024;

    private const TIMEOUT_SECONDS = 10;

    public function __construct(
        private readonly AcademicYearPreparationService $preparationService,
        private readonly ProvisionalRosterPayloadParser $payloadParser,
    ) {}

    /**
     * @return array{
     *     rows: int,
     *     students: int,
     *     academic_years: list<array{name: string, rows: int, classrooms: int, ready: bool, status: string}>,
     *     entries: list<array<string, string|null>>,
     *     conflict_count: int,
     *     can_import: bool
     * }
     */
    public function preview(string $url, User $actor): array
    {
        Gate::forUser($actor)->authorize('manageDataMaster');

        try {
            $this->assertPublicUrl($url);
            $payload = $this->fetchPayload($url);
            $rows = $this->payloadParser->parse($payload);

            return $this->buildPreview($rows);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (ConnectionException $exception) {
            throw ValidationException::withMessages([
                'api_url' => $this->connectionFailureMessage($exception),
            ]);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'api_url' => 'Respons API Siswa tidak dapat diproses oleh server SIBK.',
            ]);
        }
    }

    public function import(string $url, User $actor): ProvisionalRosterImportResult
    {
        Gate::forUser($actor)->authorize('manageDataMaster');

        $run = ExternalSyncRun::query()->create([
            'source' => 'api_siswa',
            'status' => ExternalSyncRun::STATUS_RUNNING,
            'is_full_snapshot' => false,
            'triggered_by' => $actor->getKey(),
            'received_count' => 0,
            'processed_count' => 0,
            'conflict_count' => 0,
            'summary' => 'Impor API Siswa sedang diproses.',
            'started_at' => now(),
        ]);

        $receivedCount = 0;

        try {
            $this->assertPublicUrl($url);
            $payload = $this->fetchPayload($url);
            $receivedCount = is_array($payload['data'] ?? null)
                ? count($payload['data'])
                : 0;
            $result = $this->preparationService->importRosterPayload($payload, $actor);

            $run->update([
                'status' => ExternalSyncRun::STATUS_SUCCEEDED,
                'received_count' => $receivedCount,
                'processed_count' => $result->rows,
                'summary' => sprintf(
                    'Impor API Siswa berhasil: %d data diterima dan %d data diproses.',
                    $receivedCount,
                    $result->rows,
                ),
                'finished_at' => now(),
            ]);

            return $result;
        } catch (ValidationException $exception) {
            $this->markFailed($run, $receivedCount);

            throw $exception;
        } catch (ConnectionException $exception) {
            $this->markFailed($run, $receivedCount);

            throw ValidationException::withMessages([
                'api_url' => $this->connectionFailureMessage($exception),
            ]);
        } catch (Throwable) {
            $this->markFailed($run, $receivedCount);

            throw ValidationException::withMessages([
                'api_url' => 'Respons API Siswa tidak dapat diproses.',
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function fetchPayload(string $url): array
    {
        $response = Http::acceptJson()
            ->connectTimeout(5)
            ->timeout(self::TIMEOUT_SECONDS)
            ->withoutRedirecting()
            ->withOptions(['stream' => true])
            ->get($url);

        if (! $response->successful()) {
            $message = $response->redirect()
                ? sprintf(
                    'API Siswa mengalihkan permintaan (HTTP %d). Gunakan link tujuan akhir secara langsung.',
                    $response->status(),
                )
                : sprintf('API Siswa mengembalikan HTTP %d.', $response->status());
            $this->fail($message);
        }

        $body = $this->readLimitedBody($response->toPsrResponse()->getBody());

        try {
            $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->fail('Respons API Siswa wajib berupa JSON yang valid.');
        }

        if (! is_array($payload) || array_is_list($payload)) {
            $this->fail('Respons API Siswa wajib berupa objek JSON.');
        }

        /** @var array<string, mixed> $payload */
        return $payload;
    }

    private function readLimitedBody(StreamInterface $stream): string
    {
        $body = '';

        while (! $stream->eof()) {
            $body .= $stream->read(8192);
            if (strlen($body) > self::MAX_RESPONSE_BYTES) {
                $this->fail('Ukuran respons API Siswa maksimal 2 MiB.');
            }
        }

        return $body;
    }

    private function assertPublicUrl(string $url): void
    {
        $parts = parse_url($url);
        if (! is_array($parts)
            || ! isset($parts['scheme'], $parts['host'])
            || ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            || isset($parts['user'])
            || isset($parts['pass'])
        ) {
            $this->fail('URL API Siswa harus berupa alamat HTTP atau HTTPS publik yang valid.');
        }

        $host = strtolower(trim($parts['host'], '[]'));
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            $this->fail('URL API Siswa tidak boleh mengarah ke localhost atau jaringan internal.');
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP) !== false
            ? [$host]
            : $this->resolveHost($host);

        if ($addresses === []) {
            $this->fail('Host API Siswa tidak dapat ditemukan.');
        }

        foreach ($addresses as $address) {
            if (filter_var(
                $address,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            ) === false) {
                $this->fail('URL API Siswa tidak boleh mengarah ke localhost atau jaringan internal.');
            }
        }
    }

    /**
     * @param  list<array{nisn: string, name: string, classroom: string, academic_year_name: string}>  $rows
     * @return array{
     *     rows: int,
     *     students: int,
     *     academic_years: list<array{name: string, rows: int, classrooms: int, ready: bool, status: string}>,
     *     entries: list<array<string, string|null>>,
     *     conflict_count: int,
     *     can_import: bool
     * }
     */
    private function buildPreview(array $rows): array
    {
        $rowsByYear = collect($rows)->groupBy('academic_year_name');
        $years = AcademicYear::query()
            ->whereIn('name', $rowsByYear->keys())
            ->get()
            ->keyBy('name');
        $canImport = true;
        $academicYears = [];

        foreach ($rowsByYear as $name => $yearRows) {
            $year = $years->get($name);
            [$ready, $status] = $this->yearReadiness($year);
            $canImport = $canImport && $ready;
            $academicYears[] = [
                'name' => (string) $name,
                'rows' => $yearRows->count(),
                'classrooms' => $yearRows->pluck('classroom')->unique()->count(),
                'ready' => $ready,
                'status' => $status,
            ];
        }

        $students = collect();
        foreach (array_chunk(array_values(array_unique(array_column($rows, 'nisn'))), 500) as $nisns) {
            $placeholders = implode(',', array_fill(0, count($nisns), '?'));
            $students = $students->concat(Student::query()
                ->whereRaw("TRIM(nisn) IN ($placeholders)", $nisns)
                ->get(['id', 'nisn']));
        }
        $studentsByNisn = $students->groupBy(fn (Student $student): string => trim($student->nisn));

        $memberships = collect();
        foreach ($students->pluck('id')->chunk(500) as $studentIds) {
            $memberships = $memberships->concat(StudentClassMembership::query()
                ->with('classroom:id,academic_year_id,name')
                ->whereIn('student_id', $studentIds)
                ->whereIn('academic_year_id', $years->pluck('id'))
                ->get(['id', 'student_id', 'academic_year_id', 'classroom_id']));
        }
        $membershipsByStudentAndYear = $memberships->groupBy(
            fn (StudentClassMembership $membership): string => $membership->student_id.'|'.$membership->academic_year_id,
        );

        $entries = [];
        $conflictCount = 0;
        foreach ($rows as $row) {
            $year = $years->get($row['academic_year_name']);
            [$ready] = $this->yearReadiness($year);
            $candidates = $studentsByNisn->get($row['nisn'], collect());
            $student = $candidates->first();
            $studentMemberships = $student && $year
                ? $membershipsByStudentAndYear->get($student->id.'|'.$year->id, collect())
                : collect();
            $currentClassroom = $studentMemberships->first()?->classroom?->name;
            $identityConflict = $candidates->count() > 1
                || ($student !== null && $student->nisn !== $row['nisn']);
            $classroomConflict = $studentMemberships->count() > 1
                || $studentMemberships->contains(
                    fn (StudentClassMembership $membership): bool => $membership->classroom === null
                        || $membership->classroom->academic_year_id !== $year->id
                        || mb_strtolower($membership->classroom->name) !== mb_strtolower($row['classroom']),
                );
            $status = match (true) {
                $identityConflict => 'NISN perlu diperiksa',
                $classroomConflict => 'Rombel berbeda',
                ! $ready => 'Tahun belum siap',
                $studentMemberships->isNotEmpty() => 'Sudah ada',
                default => 'Siap diimpor',
            };
            if ($identityConflict || $classroomConflict) {
                $conflictCount++;
                $entries[] = [
                    'nisn' => $row['nisn'],
                    'name' => $row['name'],
                    'academic_year' => $row['academic_year_name'],
                    'classroom' => $row['classroom'],
                    'current_classroom' => $currentClassroom,
                    'status' => $status,
                ];
            }
        }

        return [
            'rows' => count($rows),
            'students' => collect($rows)->pluck('nisn')->unique()->count(),
            'academic_years' => $academicYears,
            'entries' => $entries,
            'conflict_count' => $conflictCount,
            'can_import' => $canImport && $conflictCount === 0,
        ];
    }

    /** @return array{bool, string} */
    private function yearReadiness(mixed $year): array
    {
        if (! $year instanceof AcademicYear) {
            return [false, 'Tahun pelajaran belum dibuat di Data Master.'];
        }

        if ($year->master_source !== AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL) {
            return [false, 'Tahun pelajaran bukan tahun sementara.'];
        }

        if (! $year->is_active && $year->activated_at !== null) {
            return [false, 'Tahun pelajaran sudah selesai.'];
        }

        if ($year->starts_on === null || $year->ends_on === null) {
            return [false, 'Periode tahun pelajaran belum lengkap.'];
        }

        return [true, $year->is_active ? 'Siap menambah murid pada tahun aktif.' : 'Siap diimpor.'];
    }

    private function connectionFailureMessage(ConnectionException $exception): string
    {
        $message = strtolower($exception->getMessage());

        return match (true) {
            preg_match('/curl error 6\b/', $message) === 1 => 'Nama host API Siswa tidak dapat ditemukan oleh DNS server SIBK.',
            preg_match('/curl error 7\b/', $message) === 1 => 'Host API Siswa ditemukan, tetapi koneksinya ditolak.',
            preg_match('/curl error 28\b/', $message) === 1 => sprintf(
                'API Siswa tidak merespons dalam %d detik.',
                self::TIMEOUT_SECONDS,
            ),
            preg_match('/curl error (35|51|58|60|77|83|90)\b/', $message) === 1 => 'Koneksi HTTPS API Siswa gagal diverifikasi. Periksa sertifikat SSL server API.',
            default => 'Server SIBK tidak dapat membuka koneksi ke API Siswa.',
        };
    }

    /** @return list<string> */
    private function resolveHost(string $host): array
    {
        if (preg_match('/^(?=.{1,253}$)(?![-.])[a-z0-9.-]+(?<![-.])$/D', $host) !== 1) {
            return [];
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if (! is_array($records)) {
            return [];
        }

        $addresses = [];
        foreach ($records as $record) {
            $address = $record['ip'] ?? $record['ipv6'] ?? null;
            if (is_string($address)) {
                $addresses[] = $address;
            }
        }

        return array_values(array_unique($addresses));
    }

    private function markFailed(ExternalSyncRun $run, int $receivedCount): void
    {
        $run->update([
            'status' => ExternalSyncRun::STATUS_FAILED,
            'received_count' => $receivedCount,
            'processed_count' => 0,
            'summary' => 'Impor API Siswa gagal. Data roster tidak diubah.',
            'finished_at' => now(),
        ]);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['api_url' => $message]);
    }
}
