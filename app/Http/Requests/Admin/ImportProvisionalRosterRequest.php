<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class ImportProvisionalRosterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageDataMaster') ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:2048', 'extensions:csv'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'file.required' => 'Berkas daftar murid wajib dipilih.',
            'file.file' => 'Berkas daftar murid tidak valid.',
            'file.max' => 'Ukuran berkas daftar murid maksimal 2 MiB.',
            'file.extensions' => 'Daftar murid harus berupa berkas CSV.',
        ];
    }

    public function rosterFile(): UploadedFile
    {
        /** @var UploadedFile $file */
        $file = $this->file('file');

        return $file;
    }
}
