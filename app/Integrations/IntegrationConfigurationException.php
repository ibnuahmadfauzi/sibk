<?php

declare(strict_types=1);

namespace App\Integrations;

use RuntimeException;
use Throwable;

class IntegrationConfigurationException extends RuntimeException
{
    public function __construct(
        private readonly string $safeResultCode,
        ?Throwable $previous = null,
    ) {
        parent::__construct(self::messageFor($safeResultCode), 0, $previous);
    }

    public function resultCode(): string
    {
        return $this->safeResultCode;
    }

    private static function messageFor(string $resultCode): string
    {
        return match ($resultCode) {
            'adapter_unavailable' => 'Adapter integrasi belum tersedia.',
            'incomplete_configuration' => 'Konfigurasi integrasi belum lengkap.',
            'endpoint_not_allowed' => 'Endpoint integrasi tidak diizinkan.',
            'credential_unreadable' => 'Credential integrasi tidak dapat dibaca.',
            'busy' => 'Operasi integrasi lain sedang berjalan.',
            'timeout' => 'Batas waktu operasi integrasi terlampaui.',
            'configuration_changed' => 'Konfigurasi integrasi telah berubah.',
            'not_active' => 'Konfigurasi integrasi belum aktif.',
            default => 'Konfigurasi integrasi tidak valid.',
        };
    }
}
