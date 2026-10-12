<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

final class ProvisionalRosterCsvParser
{
    private const MAX_BYTES = 2 * 1024 * 1024;

    private const MAX_ROWS = 5000;

    /**
     * @return list<array{nisn: string, name: string, classroom: string, academic_year_name: string}>
     */
    public function parse(UploadedFile $file): array
    {
        if (! $file->isValid() || strtolower($file->getClientOriginalExtension()) !== 'csv') {
            $this->fail('Berkas harus berupa CSV yang dapat dibaca.');
        }

        $size = $file->getSize();
        if ($size === false || $size > self::MAX_BYTES) {
            $this->fail('Ukuran berkas CSV maksimum 2 MiB.');
        }

        $path = $file->getRealPath();
        $contents = $path !== false ? file_get_contents($path) : false;
        if ($contents === false || $contents === '') {
            $this->fail('Berkas CSV tidak dapat dibaca atau kosong.');
        }

        foreach (["\x00\x00\xFE\xFF", "\xFF\xFE\x00\x00", "\xFE\xFF", "\xFF\xFE"] as $unsupportedBom) {
            if (str_starts_with($contents, $unsupportedBom)) {
                $this->fail('Berkas CSV harus menggunakan UTF-8.');
            }
        }

        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        }

        if (preg_match('//u', $contents) !== 1) {
            $this->fail('Berkas CSV harus menggunakan UTF-8 yang valid.');
        }
        $this->assertValidCsvSyntax($contents);

        $stream = fopen('php://temp', 'w+b');
        if ($stream === false || fwrite($stream, $contents) === false || rewind($stream) === false) {
            $this->fail('Berkas CSV tidak dapat diproses.');
        }

        try {
            $header = fgetcsv($stream, separator: ',', enclosure: '"', escape: '');
            if ($header !== ['nisn', 'nama', 'rombel', 'tahun_pelajaran']) {
                $this->fail('Header CSV harus tepat: nisn,nama,rombel,tahun_pelajaran.');
            }

            $rows = [];
            $seenNisn = [];

            while (($row = fgetcsv($stream, separator: ',', enclosure: '"', escape: '')) !== false) {
                if (count($row) === 1 && trim((string) ($row[0] ?? '')) === '') {
                    continue;
                }

                if (count($row) !== 4
                    || ! is_string($row[0])
                    || ! is_string($row[1])
                    || ! is_string($row[2])
                    || ! is_string($row[3])) {
                    $this->fail('Setiap baris CSV harus memiliki tepat empat kolom.');
                }

                if ($this->containsControlCharacter($row[0])
                    || $this->containsControlCharacter($row[1])
                    || $this->containsControlCharacter($row[2])
                    || $this->containsControlCharacter($row[3])) {
                    $this->fail('Data CSV mengandung karakter kontrol yang tidak aman.');
                }

                $nisn = $row[0];
                $name = trim($row[1]);
                $classroom = trim($row[2]);
                $academicYearName = trim($row[3]);
                if (preg_match('/^\d{10}$/D', $nisn) !== 1) {
                    $this->fail('NISN wajib berisi tepat 10 angka.');
                }
                if ($name === '' || $classroom === '' || $academicYearName === '') {
                    $this->fail('Nama, rombel, dan tahun pelajaran wajib diisi.');
                }
                if (mb_strlen($name) > 150
                    || mb_strlen($classroom) > 100
                    || mb_strlen($academicYearName) > 20) {
                    $this->fail('Nama, rombel, atau tahun pelajaran melebihi batas panjang yang diizinkan.');
                }
                if ($this->containsUnsafeText($name)
                    || $this->containsUnsafeText($classroom)
                    || $this->containsUnsafeText($academicYearName)) {
                    $this->fail('Nama, rombel, atau tahun pelajaran mengandung karakter yang tidak aman.');
                }
                $rosterKey = $academicYearName.'|'.$nisn;
                if (isset($seenNisn[$rosterKey])) {
                    $this->fail('Satu NISN hanya boleh muncul sekali pada tahun pelajaran yang sama.');
                }

                $seenNisn[$rosterKey] = true;
                $rows[] = [
                    'nisn' => $nisn,
                    'name' => $name,
                    'classroom' => $classroom,
                    'academic_year_name' => $academicYearName,
                ];

                if (count($rows) > self::MAX_ROWS) {
                    $this->fail('Jumlah baris CSV maksimum 5.000.');
                }
            }

            if ($rows === [] || ! feof($stream)) {
                $this->fail('Berkas CSV tidak memiliki data yang dapat diproses.');
            }

            return $rows;
        } finally {
            fclose($stream);
        }
    }

    private function containsUnsafeText(string $value): bool
    {
        return preg_match('/^[=+\-@]/u', $value) === 1;
    }

    private function containsControlCharacter(string $value): bool
    {
        return preg_match('/[\x00-\x1F\x7F]/u', $value) === 1;
    }

    private function assertValidCsvSyntax(string $contents): void
    {
        $inQuotes = false;
        $atFieldStart = true;
        $afterClosingQuote = false;
        $length = strlen($contents);

        for ($index = 0; $index < $length; $index++) {
            $character = $contents[$index];

            if ($inQuotes) {
                if ($character === '"') {
                    if ($index + 1 < $length && $contents[$index + 1] === '"') {
                        $index++;
                    } else {
                        $inQuotes = false;
                        $afterClosingQuote = true;
                    }
                }

                continue;
            }

            if ($afterClosingQuote) {
                if ($character === ',') {
                    $afterClosingQuote = false;
                    $atFieldStart = true;

                    continue;
                }

                if ($character === "\n" || $character === "\r") {
                    if ($character === "\r" && $index + 1 < $length && $contents[$index + 1] === "\n") {
                        $index++;
                    }
                    $afterClosingQuote = false;
                    $atFieldStart = true;

                    continue;
                }

                $this->fail('Struktur tanda kutip pada CSV tidak valid.');
            }

            if ($atFieldStart && $character === '"') {
                $inQuotes = true;
                $atFieldStart = false;

                continue;
            }

            if ($character === '"') {
                $this->fail('Struktur tanda kutip pada CSV tidak valid.');
            }

            if ($character === ',') {
                $atFieldStart = true;
            } elseif ($character === "\n" || $character === "\r") {
                if ($character === "\r" && $index + 1 < $length && $contents[$index + 1] === "\n") {
                    $index++;
                }
                $atFieldStart = true;
            } else {
                $atFieldStart = false;
            }
        }

        if ($inQuotes) {
            $this->fail('Struktur tanda kutip pada CSV tidak valid.');
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
