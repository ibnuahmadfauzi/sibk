<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function recordChanges(
        string $action,
        Model $auditable,
        string $summary,
        User $actor,
        array $before,
        array $after,
    ): ?AuditLog {
        $changedBefore = [];
        $changedAfter = [];

        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $key) {
            $beforeExists = array_key_exists($key, $before);
            $afterExists = array_key_exists($key, $after);
            $beforeValue = $before[$key] ?? null;
            $afterValue = $after[$key] ?? null;

            if ($beforeExists === $afterExists && $beforeValue === $afterValue) {
                continue;
            }

            $changedBefore[$key] = $beforeValue;
            $changedAfter[$key] = $afterValue;
        }

        if ($changedBefore === []) {
            return null;
        }

        return $this->record($action, $auditable, $summary, $actor, $changedBefore, $changedAfter);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(
        string $action,
        Model $auditable,
        string $summary,
        ?User $actor = null,
        ?array $before = null,
        ?array $after = null,
        ?Request $request = null,
    ): AuditLog {
        $request ??= app()->bound('request') ? request() : null;

        return AuditLog::query()->create([
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'summary' => $summary,
            'before_values' => $before,
            'after_values' => $after,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
