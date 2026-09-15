<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\TemporaryPasswordResult;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TemporaryPasswordService
{
    public function __construct(private readonly AuditService $auditService) {}

    public function issue(User $target, ?User $actor = null, ?string $plainText = null): TemporaryPasswordResult
    {
        $password = $plainText ?? Str::password(16, true, true, false, false);
        $expiresAt = CarbonImmutable::now()->addHours((int) config('sibk.temporary_password_ttl_hours', 24));

        return DB::transaction(function () use ($target, $actor, $password, $expiresAt): TemporaryPasswordResult {
            $target = User::query()->lockForUpdate()->findOrFail($target->getKey());
            $target->forceFill([
                'password' => $password,
                'must_change_password' => true,
                'temporary_password_expires_at' => $expiresAt,
                'password_changed_at' => null,
            ])->save();
            DB::table('sessions')->where('user_id', $target->getKey())->delete();
            $this->auditService->record(
                action: 'account.temporary_password_issued',
                auditable: $target,
                summary: 'Kata sandi sementara akun diterbitkan.',
                actor: $actor,
            );

            return new TemporaryPasswordResult($target->refresh(), $password, $expiresAt);
        });
    }
}
