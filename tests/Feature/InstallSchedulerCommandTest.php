<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\InstallSchedulerService;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use stdClass;
use Tests\TestCase;

final class InstallSchedulerCommandTest extends TestCase
{
    private function service(string $app = '/srv/ruang bk', string $php = '/usr/bin/php', string $os = 'Linux'): InstallSchedulerService
    {
        File::shouldReceive('isDirectory')->with($app.'/storage/logs')->andReturn(true)->byDefault();
        File::shouldReceive('isWritable')->with($app.'/storage/logs')->andReturn(true)->byDefault();
        File::shouldReceive('exists')->with($app.'/storage/logs/scheduler.log')->andReturn(false)->byDefault();

        return new class($app, $php, $os) extends InstallSchedulerService
        {
            public function __construct(private string $app, private string $php, private string $os) {}

            protected function platform(): array
            {
                return [$this->os, $this->php, $this->app];
            }
        };
    }

    private function fakeCron(string $initial = '', ?string $readError = null, bool $writeFails = false, ?string $concurrent = null, string $uid = '1000'): stdClass
    {
        $cron = (object) ['calls' => [], 'state' => $initial, 'reads' => 0];
        Process::fake(function (PendingProcess $process) use ($cron, $readError, $writeFails, $concurrent, $uid) {
            $cron->calls[] = [$process->command, $process->input, $process->environment];
            if ($process->command === ['id', '-u']) {
                return Process::result($uid);
            }
            if ($process->command === ['crontab', '-l']) {
                $cron->reads++;
                if ($readError !== null) {
                    return Process::result('', $readError, 1);
                }
                if ($concurrent !== null && $cron->reads === 2) {
                    return Process::result($concurrent);
                }

                return $cron->state === ''
                    ? Process::result('', 'no crontab for school', 1)
                    : Process::result($cron->state);
            }
            if ($process->command === ['crontab', '-']) {
                if ($writeFails) {
                    return Process::result('', 'permission denied', 1);
                }
                $cron->state = (string) $process->input;

                return Process::result();
            }
            throw new RuntimeException('Unexpected process');
        });
        Process::preventStrayProcesses();

        return $cron;
    }

    public function test_installs_once_and_preserves_existing_cron(): void
    {
        $cron = $this->fakeCron("MAILTO=admin@example.test\n15 2 * * * /bin/backup\n");
        $service = $this->service();

        $this->assertSame('installed', $service->run(false)['status']);
        $this->assertStringStartsWith("MAILTO=admin@example.test\n15 2 * * * /bin/backup\n", $cron->state);
        $this->assertStringContainsString("* * * * * '/usr/bin/php' '/srv/ruang bk/artisan' schedule:run", $cron->state);
        $this->assertStringContainsString(" >> '/srv/ruang bk/storage/logs/scheduler.log' 2>&1", $cron->state);
        $this->assertSame(['crontab', '-'], $cron->calls[3][0]);
        $this->assertSame('C', $cron->calls[1][2]['LC_ALL']);
        $this->assertSame('1', $cron->calls[1][2]['NO_COLOR']);

        $before = $cron->state;
        $this->assertSame('already-installed', $service->run(false)['status']);
        $this->assertSame($before, $cron->state);
        $this->assertCount(6, $cron->calls);
    }

    public function test_empty_crontab_and_dry_run_do_not_write(): void
    {
        $cron = $this->fakeCron();
        $this->assertSame('preview', $this->service()->run(true)['status']);
        $this->assertSame('', $cron->state);
        $this->assertCount(1, $cron->calls);
        $this->assertSame(['crontab', '-l'], $cron->calls[0][0]);
    }

    public function test_unwritable_log_directory_prevents_installation(): void
    {
        $cron = $this->fakeCron();
        $service = $this->service();
        File::shouldReceive('isWritable')->with('/srv/ruang bk/storage/logs')->andReturn(false);

        $this->expectExceptionMessage('log scheduler');
        try {
            $service->run(false);
        } finally {
            $this->assertSame('', $cron->state);
            $this->assertCount(1, $cron->calls);
        }
    }

    public function test_unwritable_existing_log_file_prevents_installation(): void
    {
        $cron = $this->fakeCron();
        $service = $this->service();
        File::shouldReceive('exists')->with('/srv/ruang bk/storage/logs/scheduler.log')->andReturn(true);
        File::shouldReceive('isFile')->with('/srv/ruang bk/storage/logs/scheduler.log')->andReturn(true);
        File::shouldReceive('isWritable')->with('/srv/ruang bk/storage/logs/scheduler.log')->andReturn(false);

        $this->expectExceptionMessage('log scheduler');
        try {
            $service->run(false);
        } finally {
            $this->assertSame('', $cron->state);
            $this->assertCount(1, $cron->calls);
        }
    }

    public function test_read_error_is_not_treated_as_empty_crontab(): void
    {
        $cron = $this->fakeCron(readError: 'permission denied');
        try {
            $this->service()->run(false);
            $this->fail('Read failure must stop installation.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('crontab', $exception->getMessage());
        }
        $this->assertCount(2, $cron->calls);
    }

    public function test_conflicting_schedule_and_changed_crontab_fail_closed(): void
    {
        $cron = $this->fakeCron("* * * * * /usr/bin/php /other/artisan schedule:run\n");
        $this->expectException(RuntimeException::class);
        try {
            $this->service()->run(false);
        } finally {
            $this->assertCount(2, $cron->calls);
        }
    }

    public function test_concurrent_edit_prevents_installation(): void
    {
        $cron = $this->fakeCron("MAILTO=a@example.test\n", concurrent: "MAILTO=b@example.test\n");
        $this->expectException(RuntimeException::class);
        try {
            $this->service()->run(false);
        } finally {
            $this->assertCount(3, $cron->calls);
        }
    }

    public function test_failed_write_returns_command_failure(): void
    {
        $cron = $this->fakeCron(writeFails: true);
        $this->app->instance(InstallSchedulerService::class, $this->service());
        $this->artisan('sibk:pasang-scheduler')->assertFailed();
        $this->assertCount(4, $cron->calls);
    }

    public function test_shell_quotes_apostrophes_and_dollars(): void
    {
        $cron = $this->fakeCron();
        $this->service("/srv/school's ".chr(36).'HOME', '/opt/php 8/bin/php')->run(false);
        $this->assertStringContainsString("'/opt/php 8/bin/php' '/srv/school'\\''s ".chr(36)."HOME/artisan' schedule:run", $cron->state);
        $this->assertSame(['crontab', '-'], $cron->calls[3][0]);
    }

    public function test_rejects_paths_cron_cannot_safely_represent(): void
    {
        $cron = $this->fakeCron();
        foreach (['/srv/bad%path', "/srv/new\nline", "/srv/nul\0byte"] as $bad) {
            try {
                $this->service($bad)->run(true);
                $this->fail('Invalid path must be refused.');
            } catch (RuntimeException) {
                // Expected.
            }
        }
        $this->assertSame([], $cron->calls);
    }

    public function test_windows_and_root_installation_are_refused(): void
    {
        $cron = $this->fakeCron(uid: '0');
        try {
            $this->service(os: 'Windows')->run(false);
            $this->fail('Windows must be refused.');
        } catch (RuntimeException) {
            $this->assertSame([], $cron->calls);
        }

        $this->expectException(RuntimeException::class);
        try {
            $this->service()->run(false);
        } finally {
            $this->assertCount(1, $cron->calls);
        }
    }
}
