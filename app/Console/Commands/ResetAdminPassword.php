<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TemporaryPasswordService;
use Illuminate\Console\Command;

final class ResetAdminPassword extends Command
{
    protected $signature = 'sibk:reset-admin-password';

    protected $description = 'Pulihkan akses satu akun Admin IT dengan kata sandi sementara';

    public function handle(TemporaryPasswordService $service): int
    {
        $email = trim((string) $this->ask('Email Admin IT'));
        $admin = User::query()
            ->active()
            ->where('email', $email)
            ->whereHas('roles', fn ($roles) => $roles->where('slug', 'admin_it')->where('is_active', true))
            ->first();

        if ($admin === null) {
            $this->error('Admin IT aktif tidak ditemukan.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Kata sandi sementara baru');
        $confirmation = (string) $this->secret('Konfirmasi kata sandi sementara');
        if ($password !== $confirmation) {
            $this->error('Konfirmasi kata sandi sementara tidak sesuai.');

            return self::FAILURE;
        }
        if (mb_strlen($password) < 8 || preg_match('/\pL/u', $password) !== 1 || preg_match('/\d/', $password) !== 1) {
            $this->error('Kata sandi minimal delapan karakter serta memuat huruf dan angka.');

            return self::FAILURE;
        }

        $service->issue($admin, null, $password);
        $this->info('Akun Admin IT berhasil dipulihkan dan wajib mengganti kata sandi setelah login.');

        return self::SUCCESS;
    }
}
