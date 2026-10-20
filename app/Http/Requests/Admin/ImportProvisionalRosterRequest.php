<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\IntegrationSetting;
use App\Services\SavedApiUrlService;
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
        if ($this->routeIs('data-master.roster-imports.preview-excel')) {
            return [
                'file' => ['required', 'file', 'max:2048', 'extensions:xlsx'],
            ];
        }

        if ($this->routeIs('data-master.academic-years.roster-imports.store')) {
            return [
                'file' => ['required', 'file', 'max:2048', 'extensions:csv,xlsx'],
                'preview_hash' => ['nullable', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/'],
            ];
        }

        return [
            'api_url' => [
                Rule::requiredIf(! $this->hasFile('file') && ! $this->has('data') && ! $this->boolean('use_saved_api')),
                Rule::prohibitedIf($this->hasFile('file') || $this->has('data') || $this->boolean('use_saved_api')),
                'string',
                'max:2048',
                'url:https',
            ],
            'use_saved_api' => [
                'sometimes', 'boolean',
                Rule::prohibitedIf($this->boolean('use_saved_api') && ($this->hasFile('file') || $this->has('data') || $this->filled('api_url'))),
            ],
            'preview_hash' => ['required_with:selected_rows', 'nullable', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/'],
            'selected_rows' => [Rule::prohibitedIf(! $this->filled('api_url') && ! $this->boolean('use_saved_api')), 'array', 'max:5000'],
            'selected_rows.*' => ['required', 'integer', 'min:0', 'max:4999'],
            'file' => [
                Rule::requiredIf(! $this->filled('api_url') && ! $this->has('data') && ! $this->boolean('use_saved_api')),
                Rule::prohibitedIf($this->filled('api_url') || $this->has('data') || $this->boolean('use_saved_api')),
                'file',
                'max:2048',
                'extensions:csv,xlsx',
            ],
            'data' => [
                Rule::requiredIf(! $this->filled('api_url') && ! $this->hasFile('file') && ! $this->boolean('use_saved_api')),
                Rule::prohibitedIf($this->filled('api_url') || $this->hasFile('file') || $this->boolean('use_saved_api')),
                'array',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'api_url.required' => 'Link API Siswa atau berkas CSV/Excel wajib diisi.',
            'api_url.prohibited' => 'Pilih salah satu sumber impor: link API Siswa atau berkas CSV/Excel.',
            'api_url.max' => 'Link API Siswa terlalu panjang.',
            'api_url.url' => 'Link API Siswa harus berupa URL HTTPS yang valid.',
            'file.required' => 'Link API Siswa atau berkas CSV/Excel wajib diisi.',
            'file.prohibited' => 'Pilih salah satu sumber impor: link API Siswa atau berkas CSV/Excel.',
            'file.file' => 'Berkas daftar murid tidak valid.',
            'file.max' => 'Ukuran berkas daftar murid maksimal 2 MiB.',
            'file.extensions' => 'Daftar murid harus berupa berkas CSV atau Excel .xlsx.',
            'data.required' => 'Link API Siswa, berkas CSV/Excel, atau data API daftar murid wajib dikirim.',
            'data.prohibited' => 'Pilih hanya satu sumber impor daftar murid.',
            'data.array' => 'Data API daftar murid tidak valid.',
            'use_saved_api.boolean' => 'Pilihan API tersimpan tidak valid.',
            'use_saved_api.prohibited' => 'Pilih hanya satu sumber impor daftar murid.',
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
        return $this->filled('api_url') || $this->boolean('use_saved_api');
    }

    public function apiUrl(): string
    {
        return $this->boolean('use_saved_api')
            ? app(SavedApiUrlService::class)->get(IntegrationSetting::PROVIDER_DAPODIK, $this->user())
            : (string) $this->validated('api_url');
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
