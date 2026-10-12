<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\BkCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('case');

        return $case instanceof BkCase
            && ($this->user()?->can('update', $case) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        /** @var BkCase $case */
        $case = $this->route('case');

        return [
            'case_source_id' => ['prohibited'],
            'student_id' => ['prohibited'],
            'temporary_student_id' => ['prohibited'],
            'temporary_nisn' => ['prohibited'],
            'temporary_name' => ['prohibited'],
            'classroom_id' => ['prohibited'],
            'temporary_classroom_id' => ['prohibited'],
            'service_field_id' => ['sometimes', 'required', 'integer', Rule::exists('references', 'id')->where(
                fn ($fields) => $fields->where('category', 'service_field')
                    ->where(fn ($allowed) => $allowed->where('is_active', true)->orWhere('id', $case->service_field_id)),
            )],
            'service_date' => ['sometimes', 'required', 'date', 'before_or_equal:today'],
            'initial_info' => ['required', 'string', 'max:10000'],
            'initial_action' => ['required', 'string', 'max:10000'],
            'resolution_summary' => ['nullable', 'string', 'max:10000'],
            'action' => ['required', Rule::in(['save'])],
            'expected_updated_at' => ['required', 'date'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'case_source_id.prohibited' => 'Sumber permasalahan tidak dapat diubah.',
            'student_id.prohibited' => 'Identitas murid tidak dapat diubah.',
            'temporary_student_id.prohibited' => 'Identitas murid tidak dapat diubah.',
            'temporary_nisn.prohibited' => 'Identitas murid tidak dapat diubah.',
            'temporary_name.prohibited' => 'Identitas murid tidak dapat diubah.',
            'classroom_id.prohibited' => 'Rombel murid tidak dapat diubah.',
            'temporary_classroom_id.prohibited' => 'Rombel murid tidak dapat diubah.',
            'service_field_id.required' => 'Jenis masalah wajib dipilih.',
            'service_field_id.exists' => 'Jenis masalah tidak tersedia.',
            'service_date.required' => 'Tanggal layanan wajib diisi.',
            'service_date.before_or_equal' => 'Tanggal layanan tidak boleh berada di masa depan.',
            'initial_info.required' => 'Latar Belakang wajib diisi.',
            'initial_action.required' => 'Penanganan wajib diisi.',
            'expected_updated_at.required' => 'Muat ulang data kasus sebelum menyimpan perubahan.',
        ];
    }
}
