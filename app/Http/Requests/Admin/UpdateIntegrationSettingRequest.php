<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Integrations\IntegrationEndpointPolicy;
use App\Models\IntegrationSetting;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;

class UpdateIntegrationSettingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->errorBag = $this->provider().'_save';
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
            $provider => ['required', 'array:base_url,expected_source_identifier,api_key,remove_api_key,timeout_seconds,current_password'],
            "{$provider}.base_url" => ['present', 'nullable', 'string', 'max:500', $this->allowedEndpointRule($provider)],
            "{$provider}.expected_source_identifier" => ['present', 'nullable', 'string', 'max:100'],
            "{$provider}.api_key" => ['nullable', 'string', 'max:1000', "prohibited_if:{$provider}.remove_api_key,true"],
            "{$provider}.remove_api_key" => ['required', 'boolean'],
            "{$provider}.timeout_seconds" => ['required', 'integer', 'between:5,120'],
            "{$provider}.current_password" => ['required', 'string', 'current_password'],
            $otherProvider => ['prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $provider = $this->provider();

        return [
            "{$provider}.current_password.current_password" => 'Kata sandi saat ini tidak sesuai.',
            "{$provider}.api_key.prohibited_if" => 'Token baru tidak dapat dikirim bersamaan dengan penghapusan token.',
            "{$provider}.remove_api_key.required" => 'Pilihan penghapusan token wajib diisi.',
            "{$provider}.timeout_seconds.between" => 'Batas waktu harus antara 5 dan 120 detik.',
            'prohibited' => 'Payload provider lain tidak diizinkan.',
        ];
    }

    /** @return array<string, mixed> */
    public function setting(): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated($this->provider());

        return $data;
    }

    public function provider(): string
    {
        return (string) $this->route('provider');
    }

    public function getRedirectUrl(): string
    {
        return route('data-master.index').'#integration-'.$this->provider();
    }

    private function allowedEndpointRule(string $provider): \Closure
    {
        return static function (string $attribute, mixed $value, \Closure $fail) use ($provider): void {
            if ($value === null || $value === '') {
                return;
            }

            if (! is_string($value)) {
                return;
            }

            $configuration = config("sibk.integrations.{$provider}", []);
            $origins = $configuration['allowed_origins'] ?? [];
            $allowPrivateNetworks = $configuration['allow_private_networks'] ?? false;

            try {
                if (! is_array($origins) || ! is_bool($allowPrivateNetworks)) {
                    throw new InvalidArgumentException('Invalid endpoint policy configuration.');
                }

                (new IntegrationEndpointPolicy($origins, $allowPrivateNetworks))->assertAllowedEndpoint($value);
            } catch (InvalidArgumentException) {
                $fail('URL endpoint tidak diizinkan oleh kebijakan koneksi.');
            }
        };
    }
}
