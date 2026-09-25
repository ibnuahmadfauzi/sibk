<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class MapEtatibIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageDataMaster') ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'confirmed' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'student_id.required' => 'Pilih murid master yang menjadi tujuan pencocokan.',
            'student_id.exists' => 'Murid master yang dipilih tidak tersedia.',
            'confirmed.accepted' => 'Konfirmasi pencocokan wajib diberikan.',
        ];
    }
}
