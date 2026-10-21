<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\WebAppearance;
use App\Support\WebThemePalette;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateWebThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageDataMaster') === true;
    }

    /** @return array<string, array<int, \Illuminate\Contracts\Validation\ValidationRule|string>> */
    public function rules(): array
    {
        return [
            'theme' => ['required', Rule::in(WebAppearance::THEMES)],
            'custom_palette' => ['exclude_unless:theme,custom', 'required_if:theme,custom', 'array:primary,page,surface'],
            'custom_palette.primary' => ['required_if:theme,custom', 'regex:/\A#[0-9a-fA-F]{6}\z/D'],
            'custom_palette.page' => ['required_if:theme,custom', 'regex:/\A#[0-9a-fA-F]{6}\z/D'],
            'custom_palette.surface' => ['required_if:theme,custom', 'regex:/\A#[0-9a-fA-F]{6}\z/D'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('theme') !== 'custom' || $validator->errors()->isNotEmpty()) {
                return;
            }
            foreach (WebThemePalette::errors($this->input('custom_palette', [])) as $field => $message) {
                $validator->errors()->add($field, $message);
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'theme.required' => 'Pilih tema terlebih dahulu.',
            'theme.in' => 'Pilih tema yang tersedia.',
            'custom_palette.required_if' => 'Isi ketiga warna palet khusus.',
            'custom_palette.array' => 'Palet hanya boleh berisi warna utama, halaman, dan permukaan.',
            'custom_palette.*.required_if' => 'Warna ini wajib diisi.',
            'custom_palette.*.regex' => 'Gunakan kode warna #RRGGBB.',
        ];
    }
}
