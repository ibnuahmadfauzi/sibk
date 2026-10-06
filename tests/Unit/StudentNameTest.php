<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\BkCase;
use App\Models\Student;
use App\Models\TemporaryStudent;
use App\Support\StudentName;
use PHPUnit\Framework\TestCase;

final class StudentNameTest extends TestCase
{
    public function test_display_uses_unicode_title_case(): void
    {
        $this->assertSame('Aathirah Zhinta Chera Herwanto', StudentName::display('AATHIRAH ZHINTA CHERA HERWANTO'));
        $this->assertSame('Éléonore Putri', StudentName::display('éLÉONORE pUTRI'));
        $this->assertSame('', StudentName::display(null));
    }

    public function test_display_does_not_modify_official_or_temporary_identity(): void
    {
        $student = new Student(['name' => 'NADIA PUTRI']);
        $case = new BkCase;
        $case->setRelation('student', $student);
        $this->assertSame('Nadia Putri', $case->identityName());
        $this->assertSame('NADIA PUTRI', $student->getAttributes()['name']);

        $temporary = new TemporaryStudent(['input_name' => 'BUDI SANTOSO']);
        $case->setRelation('student', null);
        $case->setRelation('temporaryStudent', $temporary);
        $this->assertSame('Budi Santoso', $case->identityName());
        $this->assertSame('BUDI SANTOSO', $temporary->getAttributes()['input_name']);
    }
}
