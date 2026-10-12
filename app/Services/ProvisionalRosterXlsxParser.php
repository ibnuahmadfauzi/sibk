<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;
use ZipArchive;

final class ProvisionalRosterXlsxParser
{
    private const MAX_BYTES = 2 * 1024 * 1024;

    private const MAX_ROWS = 5000;

    public function __construct(private readonly ProvisionalRosterPayloadParser $payloadParser) {}

    /** @return list<array{nisn: string, name: string, classroom: string, academic_year_name: string}> */
    public function parse(UploadedFile $file): array
    {
        if (! $file->isValid() || strtolower($file->getClientOriginalExtension()) !== 'xlsx') {
            $this->fail('Berkas harus berupa Excel .xlsx yang dapat dibaca.');
        }
        $size = $file->getSize();
        if ($size === false || $size > self::MAX_BYTES) {
            $this->fail('Ukuran berkas Excel maksimum 2 MiB.');
        }

        try {
            $this->assertSafeArchive($file->getRealPath());
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $sheets = $reader->listWorksheetInfo($file->getRealPath());
            if (count($sheets) !== 1) {
                $this->fail('Berkas Excel harus memiliki tepat satu sheet daftar murid.');
            }
            if ($sheets[0]['totalRows'] > self::MAX_ROWS + 1 || $sheets[0]['totalColumns'] > 4) {
                $this->fail('Berkas Excel maksimal 5.000 baris dan empat kolom.');
            }
            $book = $reader->load($file->getRealPath());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            $this->fail('Berkas Excel tidak dapat dibaca.');
        }

        try {
            $sheet = $book->getActiveSheet();
            $headers = ['nisn', 'nama', 'rombel', 'tahun_pelajaran'];
            foreach (range('A', 'D') as $index => $column) {
                if (trim((string) $sheet->getCell($column.'1')->getValue()) !== $headers[$index]) {
                    $this->fail('Header Excel harus tepat: nisn,nama,rombel,tahun_pelajaran.');
                }
            }

            $rows = [];
            for ($number = 2; $number <= $sheet->getHighestDataRow(); $number++) {
                $cells = [];
                foreach (range('A', 'D') as $column) {
                    $cell = $sheet->getCell($column.$number);
                    if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                        $this->fail("Rumus Excel tidak diperbolehkan pada baris {$number}.");
                    }
                    $cells[] = trim((string) $cell->getFormattedValue());
                }
                if ($cells === ['', '', '', '']) {
                    continue;
                }
                $rows[] = array_combine($headers, $cells);
            }

            try {
                return $this->payloadParser->parse(['success' => true, 'data' => $rows]);
            } catch (ValidationException $exception) {
                $this->fail((string) collect($exception->errors())->flatten()->first());
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            $this->fail('Berkas Excel tidak dapat diproses.');
        } finally {
            $book->disconnectWorksheets();
        }
    }

    private function assertSafeArchive(string $path): void
    {
        $archive = new ZipArchive;
        if ($archive->open($path) !== true) {
            $this->fail('Berkas Excel tidak dapat dibaca.');
        }

        try {
            if ($archive->numFiles > 100) {
                $this->fail('Berkas Excel terlalu kompleks untuk daftar murid.');
            }
            $expandedBytes = 0;
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $entry = $archive->statIndex($index);
                if ($entry === false) {
                    $this->fail('Berkas Excel tidak dapat dibaca.');
                }
                $expandedBytes += $entry['size'];
                if ($expandedBytes > 32 * 1024 * 1024) {
                    $this->fail('Berkas Excel terlalu besar setelah dibuka.');
                }
            }
        } finally {
            $archive->close();
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
