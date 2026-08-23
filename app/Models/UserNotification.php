<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'category', 'title', 'message', 'target_type', 'target_id', 'action_route', 'action_parameters', 'deduplication_key', 'read_at'])]
class UserNotification extends Model
{
    public const CATEGORY_SCHEDULE = 'schedule';

    public const CATEGORY_ASSIGNMENT = 'assignment';

    public const CATEGORY_COORDINATION = 'coordination';

    public const CATEGORY_CORRECTION = 'correction';

    public const CATEGORY_CHANGE = 'change';

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphTo<Model, $this> */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    /** @param Builder<UserNotification> $query */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function actionUrl(): string
    {
        $allowedRoutes = [
            'cases.show',
            'consultations.show',
            'assignments.classes.index',
            'assignments.cases.index',
            'corrections.show',
            'data-master.index',
            'notifications.preview',
        ];
        if (! in_array($this->action_route, $allowedRoutes, true)) {
            return route('notifications.preview');
        }

        return route($this->action_route, $this->action_parameters ?? []);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'action_parameters' => 'array',
            'read_at' => 'datetime',
        ];
    }
}
