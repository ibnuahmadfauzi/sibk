<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class ImportProvisionalRosterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageDataMaster') ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        if ($this->routeIs('data-master.academic-years.roster-imports.store')) {
            return [
                'file' => ['required', 'file', 'max:2048', 'extensions:csv'],
            ];
        }

        return [
            'api_url' => [
                'required_without_all:file,data',
                'prohibits:file,data',
                'string',
                'max:2048',
                'url:http,https',
            ],
            'preview_hash' => ['required_with:selected_rows', Rule::prohibitedIf(! $this->filled('api_url')), 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/'],
            'selected_rows' => [Rule::prohibitedIf(! $this->filled('api_url')), 'array', 'max:5000'],
            'selected_rows.*' => ['required', 'integer', 'min:0', 'max:4999'],
            'file' => [
                'required_without_all:api_url,data',
                'prohibits:api_url,data',
                'file',
                'max:2048',
                'extensions:csv',
            ],
            'data' => [
                'required_without_all:api_url,file',
                'prohibits:api_url,file',
                'array',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'api_url.required_without_all' => 'Link API Siswa atau berkas CSV wajib diisi.',
            'api_url.prohibits' => 'Pilih salah satu sumber impor: link API Siswa atau berkas CSV.',
            'api_url.max' => 'Link API Siswa terlalu panjang.',
            'api_url.url' => 'Link API Siswa harus berupa URL HTTP atau HTTPS yang valid.',
            'file.required' => 'Berkas daftar murid wajib dipilih.',
            'file.required_without_all' => 'Link API Siswa atau berkas CSV wajib diisi.',
            'file.prohibits' => 'Pilih salah satu sumber impor: link API Siswa atau berkas CSV.',
            'file.file' => 'Berkas daftar murid tidak valid.',
            'file.max' => 'Ukuran berkas daftar murid maksimal 2 MiB.',
            'file.extensions' => 'Daftar murid harus berupa berkas CSV.',
            'data.required_without_all' => 'Link API Siswa, berkas CSV, atau data API daftar murid wajib dikirim.',
            'data.prohibits' => 'Pilih hanya satu sumber impor daftar murid.',
            'data.array' => 'Data API daftar murid tidak valid.',
        ];
    }

    public function rosterFile(): UploadedFile
    {
        /** @var UploadedFile $file */
        $file = $this->file('file');

        return $file;
    }

    public function hasRosterPayload(): bool
    {
        return $this->has('data');
    }

    public function hasApiUrl(): bool
    {
        return $this->filled('api_url');
    }

    public function apiUrl(): string
    {
        return (string) $this->validated('api_url');
    }

    /** @return list<int> */
    public function selectedRows(): array
    {
        return array_map('intval', array_values($this->validated('selected_rows', [])));
    }

    public function previewHash(): ?string
    {
        return $this->validated('preview_hash');
    }

    /** @return array<string, mixed> */
    public function rosterPayload(): array
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->all();

        return $payload;
    }
}
