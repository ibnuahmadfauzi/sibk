<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\IntegrationSetting;
use Illuminate\Foundation\Http\FormRequest;

class IntegrationActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageDataMaster') ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $provider = $this->provider();
        $otherProvider = $provider === IntegrationSetting::PROVIDER_DAPODIK
            ? IntegrationSetting::PROVIDER_ETATIB
            : IntegrationSetting::PROVIDER_DAPODIK;

        return [
            $provider => ['required', 'array:current_password'],
            "{$provider}.current_password" => ['required', 'string', 'current_password'],
            $otherProvider => ['prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'prohibited' => 'Payload provider lain tidak diizinkan.',
        ];
    }

    public function provider(): string
    {
        return (string) $this->route('provider');
    }

    public function getRedirectUrl(): string
    {
        return route('data-master.index').'#integration-'.$this->provider();
    }
}
