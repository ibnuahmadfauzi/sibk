<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Achievement;
use Illuminate\Foundation\Http\FormRequest;

class ImportAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Achievement::class) ?? false;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['file' => ['required', 'file', 'mimes:xlsx', 'max:2048']];
    }
}
