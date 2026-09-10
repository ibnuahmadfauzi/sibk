<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\IntegrationSetting;
use Illuminate\Foundation\Http\FormRequest;

class IntegrationActionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->errorBag = $this->provider().'_'.$this->action();
    }

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
        $provider = $this->provider();
        $otherProvider = $provider === IntegrationSetting::PROVIDER_DAPODIK
            ? IntegrationSetting::PROVIDER_ETATIB
            : IntegrationSetting::PROVIDER_DAPODIK;

        return [
            "{$provider}.required" => 'Data tindakan wajib dikirim.',
            "{$provider}.array" => 'Bentuk data tindakan tidak valid.',
            "{$provider}.current_password.current_password" => 'Kata sandi saat ini tidak sesuai.',
            "{$otherProvider}.prohibited" => 'Data sumber lain tidak boleh dikirim bersama tindakan ini.',
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

    private function action(): string
    {
        return match ($this->route()?->getName()) {
            'data-master.integrations.activate' => 'activate',
            'data-master.integrations.deactivate' => 'deactivate',
            default => 'test',
        };
    }
}
