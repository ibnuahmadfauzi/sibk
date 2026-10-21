<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\WebAppearance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateWebThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageDataMaster') === true;
    }

    /** @return array<string, array<int, \Illuminate\Contracts\Validation\ValidationRule|string>> */
    public function rules(): array
    {
        return ['theme' => ['required', Rule::in(WebAppearance::THEMES)]];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['theme.required' => 'Pilih tema terlebih dahulu.', 'theme.in' => 'Pilih tema yang tersedia.'];
    }
}
