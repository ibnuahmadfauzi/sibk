<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSavedApiUrlRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->errorBag = 'api_connections_'.$this->route('provider');
    }

    public function authorize(): bool
    {
        return $this->user()?->can('manageDataMaster') ?? false;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'api_url' => ['required', 'string', 'max:2048', 'url:https'],
            'current_password' => ['required', 'string', 'current_password:web'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'api_url.required' => 'Link API wajib diisi.',
            'api_url.url' => 'Link API harus berupa alamat HTTPS yang valid.',
            'api_url.max' => 'Link API terlalu panjang.',
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
        ];
    }

    public function apiUrl(): string
    {
        return (string) $this->validated('api_url');
    }

    public function getRedirectUrl(): string
    {
        return route('data-master.index', ['tab' => $this->route('provider')]);
    }
}
