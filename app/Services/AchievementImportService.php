<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Achievement;
use App\Models\ReferenceValue;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Throwable;

class AchievementImportService
{
    public function __construct(private readonly AchievementService $achievements) {}

    public function import(UploadedFile $file, User $actor): int
    {
        abort_unless($actor->can('create', Achievement::class), 403);

        try {
            $reader = IOFactory::createReader('Xlsx');
            $book = $reader->load($file->getRealPath());
        } catch (Throwable) {
            throw ValidationException::withMessages(['file' => 'Berkas Excel tidak dapat dibaca.']);
        }

        try {
            $sheet = $book->getActiveSheet();
            $headers = ['nisn', 'jenis', 'tingkat', 'kegiatan', 'penyelenggara', 'tanggal', 'hasil'];
            $actual = [];
            foreach (range('A', 'G') as $column) {
                $actual[] = mb_strtolower(trim((string) $sheet->getCell($column.'1')->getValue()));
            }
            if ($actual !== $headers) {
                throw ValidationException::withMessages(['file' => 'Kolom Excel harus berurutan: '.implode(', ', $headers).'.']);
            }

            $lastRow = $sheet->getHighestDataRow();
            if ($lastRow > 1001) {
                throw ValidationException::withMessages(['file' => 'Maksimal 1.000 baris prestasi per berkas.']);
            }

            $types = ReferenceValue::query()->active()->forCategory('achievement_type')->pluck('id', 'code');
            $levels = ReferenceValue::query()->active()->forCategory('achievement_level')->pluck('id', 'code');
            $rows = [];
            $errors = [];
            for ($number = 2; $number <= $lastRow; $number++) {
                $cells = [];
                foreach (range('A', 'G') as $column) {
                    $cell = $sheet->getCell($column.$number);
                    if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                        $errors[] = 'Baris '.$number.': rumus Excel tidak diperbolehkan.';
                        continue 2;
                    }
                    $cells[] = trim((string) $cell->getFormattedValue());
                }
                if (count(array_filter($cells, fn (string $value): bool => $value !== '')) === 0) {
                    continue;
                }

                [$nisn, $type, $level, $activity, $organizer, $date, $result] = $cells;
                $dateCell = $sheet->getCell('F'.$number);
                if (Date::isDateTime($dateCell) && is_numeric($dateCell->getValue())) {
                    $date = Date::excelToDateTimeObject((float) $dateCell->getValue())->format('Y-m-d');
                }
                $student = Student::query()->where('nisn', $nisn)->first();
                $data = [
                    'student_id' => $student?->getKey(),
                    'type_id' => $types->get($type),
                    'level_id' => $levels->get($level),
                    'activity_name' => $activity,
                    'organizer' => $organizer,
                    'achievement_date' => $date,
                    'result' => $result,
                ];
                $validator = Validator::make($data, [
                    'student_id' => ['required', 'integer'],
                    'type_id' => ['required', 'integer'],
                    'level_id' => ['required', 'integer'],
                    'activity_name' => ['required', 'string', 'max:250'],
                    'organizer' => ['required', 'string', 'max:200'],
                    'achievement_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
                    'result' => ['required', 'string', 'max:250'],
                ]);
                if ($validator->fails()) {
                    $errors[] = 'Baris '.$number.': NISN, kode jenis/tingkat, atau data prestasi tidak valid.';
                    continue;
                }
                if (! Student::query()->availableForService($date)->whereKey($student->getKey())->exists()) {
                    $errors[] = 'Baris '.$number.': murid tidak tersedia pada tanggal prestasi.';
                    continue;
                }
                $rows[] = $data;
            }
            if ($errors !== []) {
                throw ValidationException::withMessages(['file' => $errors]);
            }
            if ($rows === []) {
                throw ValidationException::withMessages(['file' => 'Berkas Excel tidak berisi prestasi.']);
            }

            DB::transaction(function () use ($rows, $actor): void {
                foreach ($rows as $row) {
                    $this->achievements->create($row, $actor);
                }
            });

            return count($rows);
        } finally {
            $book->disconnectWorksheets();
        }
    }
}
