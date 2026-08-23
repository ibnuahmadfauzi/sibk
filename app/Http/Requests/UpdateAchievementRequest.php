<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Achievement;

class UpdateAchievementRequest extends StoreAchievementRequest
{
    public function authorize(): bool
    {
        $achievement = $this->route('achievement');

        return $achievement instanceof Achievement
            && ($this->user()?->can('update', $achievement) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return $this->achievementRules();
    }
}
