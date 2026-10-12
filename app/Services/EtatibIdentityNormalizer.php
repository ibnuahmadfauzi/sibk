<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;

final class EtatibIdentityNormalizer
{
    public function name(string $value): string
    {
        return Str::upper((string) preg_replace('/\s+/u', ' ', trim($value)));
    }

    public function nameHash(string $value): string
    {
        return hash('sha256', $this->name($value));
    }

    public function key(string $canonicalNisn, string $sourceName): string
    {
        return $canonicalNisn.'|'.$this->nameHash($sourceName);
    }
}
