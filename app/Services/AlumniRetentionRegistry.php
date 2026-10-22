<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\IntegrationSetting;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AlumniRetentionRegistry
{
    private ?string $verifiedFingerprint = null;

    public function lockImports(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new RuntimeException('Penguncian retensi memerlukan transaksi.');
        }

        foreach (IntegrationSetting::PROVIDERS as $provider) {
            DB::table('integration_settings')->insertOrIgnore([
                'provider' => $provider, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('integration_settings')->where('provider', $provider)->lockForUpdate()->first();
        }
        $this->verifiedFingerprint = null;
    }

    public function assertReady(): void
    {
        $key = $this->key();
        if ($key === null) {
            throw new RuntimeException('ALUMNI_RETENTION_KEY wajib diatur sebelum penghapusan alumni.');
        }
        $this->verifyFingerprint($key);
    }

    public function record(Student $student): void
    {
        $this->assertReady();
        $key = $this->key();
        foreach (['nisn' => $student->nisn, 'dapodik_id' => $student->dapodik_id] as $kind => $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                DB::table('alumni_retention_markers')->insertOrIgnore([
                    'kind' => $kind,
                    'digest' => $this->digest($kind, $value, $key),
                    'key_fingerprint' => $this->fingerprint($key),
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function blocked(?string $nisn, ?string $dapodikId = null): bool
    {
        $key = $this->key();
        if ($key === null) {
            if (DB::table('alumni_retention_markers')->exists()) {
                throw new RuntimeException('Kunci penanda retensi tidak tersedia; impor dihentikan.');
            }

            return false;
        }
        $this->verifyFingerprint($key);
        $digests = [];
        foreach (['nisn' => $nisn, 'dapodik_id' => $dapodikId] as $kind => $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                $digests[] = $this->digest($kind, $value, $key);
            }
        }

        return $digests !== [] && DB::table('alumni_retention_markers')->whereIn('digest', $digests)->exists();
    }

    public function assertImportAllowed(?string $nisn, ?string $dapodikId = null): void
    {
        if ($this->blocked($nisn, $dapodikId)) {
            throw ValidationException::withMessages([
                'retention' => 'Identitas ini sudah dihapus sesuai kebijakan retensi dan tidak dapat diimpor kembali.',
            ]);
        }
    }

    private function key(): ?string
    {
        $configured = config('alumni_retention.key');
        if ($configured === null || $configured === '') {
            return null;
        }
        $key = is_string($configured) && str_starts_with($configured, 'base64:')
            ? base64_decode(substr($configured, 7), strict: true)
            : false;
        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('ALUMNI_RETENTION_KEY harus berupa base64: dengan 32 byte acak.');
        }

        return $key;
    }

    private function verifyFingerprint(#[\SensitiveParameter] string $key): void
    {
        $fingerprint = $this->fingerprint($key);
        if ($this->verifiedFingerprint === $fingerprint) {
            return;
        }
        if (DB::table('alumni_retention_markers')->where('key_fingerprint', '!=', $fingerprint)->exists()) {
            throw new RuntimeException('Kunci penanda retensi berubah; pulihkan kunci asli sebelum melanjutkan.');
        }
        $this->verifiedFingerprint = $fingerprint;
    }

    private function fingerprint(#[\SensitiveParameter] string $key): string
    {
        return hash_hmac('sha256', 'sibk:alumni-retention:key:v1', $key);
    }

    private function digest(string $kind, string $value, #[\SensitiveParameter] string $key): string
    {
        return hash_hmac('sha256', 'sibk:alumni-retention:v1:'.$kind."\0".$value, $key);
    }
}
