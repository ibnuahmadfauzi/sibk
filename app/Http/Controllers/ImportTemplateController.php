<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Services\ImportTemplateExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ImportTemplateController extends Controller
{
    public function roster(Request $request, ImportTemplateExporter $exporter): BinaryFileResponse
    {
        Gate::forUser($request->user())->authorize('manageDataMaster');

        return $this->download($exporter->roster(), 'template-daftar-murid.xlsx');
    }

    public function achievement(Request $request, ImportTemplateExporter $exporter): BinaryFileResponse
    {
        abort_unless($request->user()->can('create', Achievement::class), 403);

        return $this->download($exporter->achievement(), 'template-prestasi.xlsx');
    }

    private function download(string $path, string $filename): BinaryFileResponse
    {
        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store',
        ])->deleteFileAfterSend(true);
    }
}
