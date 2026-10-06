<?php

declare(strict_types=1);

namespace App\Support;

final class StudentName
{
    public static function display(?string $name): string
    {
        return mb_convert_case(trim($name ?? ''), MB_CASE_TITLE, 'UTF-8');
    }
}
