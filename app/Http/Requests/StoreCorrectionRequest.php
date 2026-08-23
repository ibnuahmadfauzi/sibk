<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Correction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Correction::class) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'target_type' => ['required', Rule::in(['case', 'follow_up', 'consultation', 'achievement', 'student'])],
            'target_id' => ['required', 'integer', 'min:1'],
            'field_name' => ['required', 'string', 'max:100'],
            'proposed_value' => ['present', 'nullable', 'string', 'max:20000'],
            'reason' => ['required', 'string', 'max:10000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'target_type.required' => 'Jenis objek koreksi wajib dipilih.',
            'target_type.in' => 'Jenis objek koreksi tidak tersedia.',
            'target_id.required' => 'Objek target wajib dipilih.',
            'field_name.required' => 'Atribut yang dikoreksi wajib dipilih.',
            'proposed_value.present' => 'Nilai usulan wajib dikirim.',
            'reason.required' => 'Alasan pengajuan koreksi wajib diisi.',
        ];
    }
}
