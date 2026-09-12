<?php

declare(strict_types=1);

namespace App\Support;

final class ServiceRecordStatus
{
    public const string NEW = 'baru';

    public const string IN_PROGRESS = 'sedang_diproses';

    public const string NEEDS_FOLLOW_UP = 'membutuhkan_tindak_lanjut';

    public const string COMPLETED = 'selesai';

    public const string CANCELLED = 'dibatalkan';

    /** @var list<string> */
    private const array CODES = [
        self::NEW,
        self::IN_PROGRESS,
        self::NEEDS_FOLLOW_UP,
        self::COMPLETED,
        self::CANCELLED,
    ];

    /** @var array<string, string> */
    private const array LABELS = [
        self::NEW => 'Baru dicatat',
        self::IN_PROGRESS => 'Sedang diproses',
        self::NEEDS_FOLLOW_UP => 'Membutuhkan tindak lanjut',
        self::COMPLETED => 'Selesai',
        self::CANCELLED => 'Dibatalkan',
    ];

    /** @var list<string> */
    private const array TERMINAL_CODES = [
        self::COMPLETED,
        self::CANCELLED,
    ];

    /** @return list<string> */
    public static function codes(): array
    {
        return self::CODES;
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        return self::LABELS;
    }

    public static function initialCode(): string
    {
        return self::NEW;
    }

    /** @return list<string> */
    public static function terminalCodes(): array
    {
        return self::TERMINAL_CODES;
    }

    public static function isTerminal(?string $code): bool
    {
        return $code !== null && in_array($code, self::TERMINAL_CODES, true);
    }

    public static function label(string $code): ?string
    {
        return self::LABELS[$code] ?? null;
    }
}
