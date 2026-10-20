<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\IntegrationSetting;
use App\Services\SavedApiUrlService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SyncEtatibApiRequest extends FormRequest
{
    protected $errorBag = 'etatib_api';

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
            'identity_decisions' => ['sometimes', 'array', 'max:10000'],
            'identity_decisions.*.nisn' => ['required', 'string', 'regex:/^\\d{10}$/'],
            'identity_decisions.*.name' => ['required', 'string', 'max:255'],
            'identity_decisions.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'duplicate_decisions' => ['sometimes', 'array', 'max:10000'],
            'duplicate_decisions.*' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'api_url.required' => 'Link API e-Tatib wajib diisi.',
            'api_url.max' => 'Link API e-Tatib terlalu panjang.',
            'api_url.url' => 'Link API e-Tatib harus berupa URL HTTPS yang valid.',
        ];
    }

    public function apiUrl(): string
    {
        return $this->boolean('use_saved_api')
            ? app(SavedApiUrlService::class)->get(IntegrationSetting::PROVIDER_ETATIB, $this->user())
            : (string) $this->validated('api_url');
    }

    /** @return list<array{nisn: string, name: string, student_id: int}> */
    public function identityDecisions(): array
    {
        return array_values($this->validated('identity_decisions', []));
    }

    /** @return list<string> */
    public function duplicateDecisions(): array
    {
        return array_values($this->validated('duplicate_decisions', []));
    }
}
