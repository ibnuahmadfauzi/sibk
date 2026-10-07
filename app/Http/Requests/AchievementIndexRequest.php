<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Achievement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'level_id' => ['nullable', 'integer', Rule::exists('references', 'id')->where('category', 'achievement_level')->where('is_active', true)],
            'sort' => ['nullable', Rule::in(['murid', 'kegiatan', 'tanggal'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'level_id.exists' => 'Tingkat prestasi tidak tersedia.',
        ];
    }
}
