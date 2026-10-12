<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Achievement;
use Illuminate\Foundation\Http\FormRequest;

class DeleteAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $achievement = $this->route('achievement');

        return $achievement instanceof Achievement
            && ($this->user()?->can('delete', $achievement) ?? false);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['expected_updated_at' => ['required', 'date']];
    }
}
