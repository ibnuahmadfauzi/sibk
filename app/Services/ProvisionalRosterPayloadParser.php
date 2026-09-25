<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Validation\ValidationException;

final class ProvisionalRosterPayloadParser
{
    private const MAX_ROWS = 5000;

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{nisn: string, name: string, classroom: string, academic_year_name: string}>
     */
    public function parse(array $payload): array
    {
        if (($payload['success'] ?? null) !== true) {
            $this->fail('Respons API daftar murid tidak berhasil.');
        }

        if (! isset($payload['data']) || ! is_array($payload['data'])) {
            $this->fail('Respons API daftar murid harus memiliki data siswa.');
        }

        if ($payload['data'] === []) {
            $this->fail('Respons API daftar murid tidak memiliki data yang dapat diproses.');
        }

        if (count($payload['data']) > self::MAX_ROWS) {
            $this->fail('Jumlah data API maksimum 5.000 murid.');
        }

        $rows = [];
        $seenNisn = [];
        foreach ($payload['data'] as $item) {
            if (! is_array($item)) {
                $this->fail('Setiap data siswa dari API harus berupa objek.');
            }

            foreach (['nisn', 'nama', 'rombel', 'tahun_pelajaran'] as $field) {
                if (! array_key_exists($field, $item) || ! is_string($item[$field])) {
                    $this->fail('Data API wajib memuat nisn, nama, rombel, dan tahun_pelajaran.');
                }
                if ($this->containsControlCharacter($item[$field])) {
                    $this->fail('Data API mengandung karakter kontrol yang tidak aman.');
                }
            }

            $nisn = $item['nisn'];
            $name = trim($item['nama']);
            $classroom = trim($item['rombel']);
            $academicYearName = trim($item['tahun_pelajaran']);

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
        }

        return $rows;
    }

    private function containsUnsafeText(string $value): bool
    {
        return preg_match('/^[=+\-@]/u', $value) === 1;
    }

    private function containsControlCharacter(string $value): bool
    {
        return preg_match('/[\x00-\x1F\x7F]/u', $value) === 1;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['data' => $message]);
    }
}
