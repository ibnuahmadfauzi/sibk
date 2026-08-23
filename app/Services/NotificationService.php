<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BkCase;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class NotificationService
{
    /** @param iterable<User> $recipients @param array<string, int|string> $actionParameters */
    public function send(
        iterable $recipients,
        string $category,
        string $title,
        string $message,
        ?Model $target,
        ?string $actionRoute,
        array $actionParameters,
        string $deduplicationKey,
    ): void {
        if (! in_array($category, $this->categories(), true)) {
            throw ValidationException::withMessages(['category' => 'Kategori notifikasi tidak tersedia.']);
        }

        foreach ($recipients as $recipient) {
            if (! $recipient->is_active) {
                continue;
            }
            UserNotification::query()->updateOrCreate(
                ['user_id' => $recipient->getKey(), 'deduplication_key' => $deduplicationKey],
                [
                    'category' => $category,
                    'title' => $title,
                    'message' => $message,
                    'target_type' => $target?->getMorphClass(),
                    'target_id' => $target?->getKey(),
                    'action_route' => $actionRoute,
                    'action_parameters' => $actionParameters,
                ],
            );
        }
    }

    /** @return Collection<int, User> */
    public function activeUsersWithRole(string $role): Collection
    {
        return User::query()->active()->whereHas('roles', fn ($roles) => $roles
            ->where('slug', $role)->where('is_active', true))->get();
    }

    /** @return Collection<int, User> */
    public function activeCaseTeachers(BkCase $case): Collection
    {
        return User::query()->active()->whereHas('roles', fn ($roles) => $roles
            ->where('slug', 'guru_bk')->where('is_active', true))
            ->whereIn('id', $case->assignments()->effectiveOn(now())->select('user_id'))
            ->get();
    }

    /** @return list<string> */
    private function categories(): array
    {
        return [
            UserNotification::CATEGORY_SCHEDULE,
            UserNotification::CATEGORY_ASSIGNMENT,
            UserNotification::CATEGORY_COORDINATION,
            UserNotification::CATEGORY_CORRECTION,
            UserNotification::CATEGORY_CHANGE,
        ];
    }
}
