<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

final class ReportDocumentExporter
{
    /**
     * @param array<string, mixed> $report
     * @param array<string, array{role: string, name: string}> $signatories
     * @return array{path: string, filename: string, content_type: string}
     */
    public function export(
        string $format,
        array $report,
        array $signatories,
    ): array {
        return match ($format) {
            'xlsx' => $this->excel($report),
            'doc' => $this->word($report, $signatories),
            default => throw new RuntimeException('Format laporan tidak tersedia.'),
        };
    }

    /** @param array<string, mixed> $report @return array{path: string, filename: string, content_type: string} */
    private function excel(array $report): array
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Layanan BK');
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'LAPORAN LAYANAN BIMBINGAN DAN KONSELING');
        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', $this->filterSummary($report));
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = [
            'No',
            'Hari / Tanggal',
            'Nama / Kelas',
            'Jenis Layanan',
            'Permasalahan',
            'Penanganan',
            'Tindak Lanjut / Status',
        ];
        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 4], $header);
        }

        foreach ($report['rows'] as $index => $row) {
            $excelRow = $index + 5;
            $values = [
                $index + 1,
                $row['date']->locale('id')->translatedFormat('l')."\n"
                    .$row['date']->locale('id')->translatedFormat('d F Y'),
                mb_strtoupper($row['name'])."\n".$row['classroom'],
                $row['service']."\n".$row['service_field'],
                $row['problem'],
                $row['handling']."\n".$row['detail_label'].': '.$row['detail_note'],
                $row['follow_up_label'],
            ];

            foreach ($values as $columnIndex => $value) {
                $coordinate = Coordinate::stringFromColumnIndex($columnIndex + 1).$excelRow;
                if ($columnIndex === 0) {
                    $sheet->setCellValue($coordinate, $value);
                    continue;
                }

                $sheet->setCellValueExplicit(
                    $coordinate,
                    $this->safeSpreadsheetText((string) $value),
                    DataType::TYPE_STRING,
                );
            }
        }

        $lastRow = max(4, count($report['rows']) + 4);
        $sheet->getStyle("A4:G{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A4:G4')->getFont()->setBold(true);
        $sheet->getStyle('A4:G4')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FFDCE6F1');
        $sheet->getStyle("A4:G{$lastRow}")->getAlignment()
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setWrapText(true);
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(28);
        $sheet->getColumnDimension('D')->setWidth(22);
        $sheet->getColumnDimension('E')->setWidth(38);
        $sheet->getColumnDimension('F')->setWidth(45);
        $sheet->getColumnDimension('G')->setWidth(24);
        $sheet->freezePane('A5');
        $sheet->setAutoFilter("A4:G{$lastRow}");

        $path = $this->temporaryPath();
        try {
            (new Xlsx($spreadsheet))->save($path);
        } catch (\Throwable $exception) {
            @unlink($path);
            throw $exception;
        } finally {
            $spreadsheet->disconnectWorksheets();
        }

        return [
            'path' => $path,
            'filename' => $this->filename('xlsx'),
            'content_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
    }

    /**
     * @param array<string, mixed> $report
     * @param array<string, array{role: string, name: string}> $signatories
     * @return array{path: string, filename: string, content_type: string}
     */
    private function word(array $report, array $signatories): array
    {
        $path = $this->temporaryPath();
        try {
            $content = View::make('pages.reports.exports.word', [
                'report' => $report,
                'signatories' => $signatories,
                'filterSummary' => $this->filterSummary($report),
            ])->render();

            if (file_put_contents($path, $content) === false) {
                throw new RuntimeException('Dokumen Word tidak dapat dibuat.');
            }
        } catch (\Throwable $exception) {
            @unlink($path);
            throw $exception;
        }

        return [
            'path' => $path,
            'filename' => $this->filename('doc'),
            'content_type' => 'application/msword',
        ];
    }

    /** @param array<string, mixed> $report */
    private function filterSummary(array $report): string
    {
        $classroom = collect($report['filter_options']['classrooms'])
            ->firstWhere('id', $report['filters']['classroom_id']);
        $service = match ($report['filters']['service_type']) {
            'case' => 'Catatan Permasalahan',
            'consultation' => 'Catatan Konsultasi',
            default => 'Semua Layanan',
        };

        return sprintf(
            'Tahun Ajaran %s | Kelas %s | %s',
            $report['academic_year']?->name ?? '—',
            $classroom?->name ?? 'Semua Kelas',
            $service,
        );
    }

    private function safeSpreadsheetText(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/u', $value) === 1
            ? "'".$value
            : $value;
    }

    private function temporaryPath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sibk-report-');
        if ($path === false) {
            throw new RuntimeException('File sementara laporan tidak dapat dibuat.');
        }

        return $path;
    }

    private function filename(string $extension): string
    {
        return sprintf(
            'laporan-layanan-bk-%s.%s',
            now()->format('Ymd-His'),
            $extension,
        );
    }
}
