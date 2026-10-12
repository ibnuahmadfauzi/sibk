<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class PreviewApiSiswaRosterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageDataMaster') ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'api_url' => ['required', 'string', 'max:2048', 'url:http,https'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'api_url.required' => 'Link API Siswa wajib diisi.',
            'api_url.max' => 'Link API Siswa terlalu panjang.',
            'api_url.url' => 'Link API Siswa harus berupa URL HTTP atau HTTPS yang valid.',
        ];
    }

    public function apiUrl(): string
    {
        return (string) $this->validated('api_url');
    }
}
