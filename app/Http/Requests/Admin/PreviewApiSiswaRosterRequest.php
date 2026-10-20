<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\IntegrationSetting;
use App\Services\SavedApiUrlService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'api_url' => [Rule::requiredIf(! $this->boolean('use_saved_api')), Rule::prohibitedIf($this->boolean('use_saved_api')), 'string', 'max:2048', 'url:https'],
            'use_saved_api' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'api_url.required' => 'Link API Siswa wajib diisi.',
            'api_url.max' => 'Link API Siswa terlalu panjang.',
            'api_url.url' => 'Link API Siswa harus berupa URL HTTPS yang valid.',
        ];
    }

    public function apiUrl(): string
    {
        return $this->boolean('use_saved_api')
            ? app(SavedApiUrlService::class)->get(IntegrationSetting::PROVIDER_DAPODIK, $this->user())
            : (string) $this->validated('api_url');
    }
}
