<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Achievement;
use Illuminate\Foundation\Http\FormRequest;

class AchievementIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Achievement::class) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'classroom_id.exists' => 'Kelas tidak tersedia.',
        ];
    }
}
