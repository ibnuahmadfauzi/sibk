<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ReferenceValue;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Throwable;

final class ImportTemplateExporter
{
    public function roster(): string
    {
        return $this->write(['nisn', 'nama', 'rombel', 'tahun_pelajaran']);
    }

    public function achievement(): string
    {
        $types = ReferenceValue::query()->active()->forCategory('achievement_type')->orderBy('sort_order')->pluck('code');
        $levels = ReferenceValue::query()->active()->forCategory('achievement_level')->orderBy('sort_order')->pluck('code');

        return $this->write(
            ['nisn', 'jenis', 'tingkat', 'kegiatan', 'penyelenggara', 'tanggal', 'hasil'],
            ['B' => $types->all(), 'C' => $levels->all()],
        );
    }

    /** @param list<string> $headers @param array<string, list<string>> $codes */
    private function write(array $headers, array $codes = []): string
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle($codes === [] ? 'Daftar Murid' : 'Prestasi');
        $sheet->fromArray($headers, null, 'A1');
        $lastColumn = chr(ord('A') + count($headers) - 1);
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true)->getColor()->setARGB(Color::COLOR_WHITE);
        $sheet->getStyle("A1:{$lastColumn}1")->getFill()
            ->setFillType('solid')->getStartColor()->setARGB('FF174A70');
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getColumnDimension('A')->setWidth(17);
        $sheet->getStyle($codes === [] ? 'A2:A5001' : 'A2:A1001')
            ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getComment('A1')->getText()->createTextRun('Isi tepat 10 angka NISN. Nol di depan harus tetap ditulis.');
        $sheet->getComment($codes === [] ? 'D1' : 'F1')->getText()
            ->createTextRun($codes === [] ? 'Contoh: 2026/2027.' : 'Isi tanggal YYYY-MM-DD, contoh: 2026-09-18.');
        foreach (range('B', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setWidth($column === 'D' && $codes === [] ? 21 : 25);
        }
        $sheet->freezePane('A2');

        foreach ($codes as $column => $values) {
            $values = array_filter($values, static fn (string $code): bool => ! str_contains($code, ',') && ! str_contains($code, '"'));
            $list = implode(',', $values);
            $sheet->getComment($column.'1')->getText()->createTextRun('Pilih kode: '.$list);
            // shortcut: daftar langsung dibatasi Excel 255 karakter; gunakan rentang bernama jika kode bertambah.
            if ($list === '' || strlen($list) > 253) {
                continue;
            }
            $validation = new DataValidation;
            $validation->setType(DataValidation::TYPE_LIST)
                ->setErrorStyle(DataValidation::STYLE_STOP)
                ->setAllowBlank(true)
                ->setShowDropDown(true)
                ->setShowErrorMessage(true)
                ->setError('Pilih kode yang tersedia.')
                ->setFormula1('"'.$list.'"');
            $sheet->setDataValidation("{$column}2:{$column}1001", $validation);
        }

        $path = tempnam(sys_get_temp_dir(), 'sibk-template-');
        if ($path === false) {
            $book->disconnectWorksheets();
            throw new RuntimeException('Template Excel tidak dapat dibuat.');
        }
        try {
            (new Xlsx($book))->save($path);
        } catch (Throwable $exception) {
            @unlink($path);
            throw $exception;
        } finally {
            $book->disconnectWorksheets();
        }

        return $path;
    }
}
