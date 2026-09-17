<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Consultation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $consultation = $this->route('consultation');

        return $consultation instanceof Consultation
            && ($this->user()?->can('update', $consultation) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'student_id' => ['prohibited'],
            'temporary_student_id' => ['prohibited'],
            'temporary_nisn' => ['prohibited'],
            'temporary_name' => ['prohibited'],
            'service_field_id' => ['required', 'integer', Rule::exists('references', 'id')->where('category', 'service_field')->where('is_active', true)],
            'session_date' => ['required', 'date', 'before_or_equal:today'],
            'problem' => ['required', 'string', 'max:10000'],
            'handling' => ['required', 'string', 'max:10000'],
            'result' => ['required', 'string', 'max:10000'],
            'expected_updated_at' => ['required', 'date'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'student_id.prohibited' => 'Identitas murid tidak dapat diubah.',
            'temporary_student_id.prohibited' => 'Identitas murid tidak dapat diubah.',
            'temporary_nisn.prohibited' => 'Identitas murid tidak dapat diubah.',
            'temporary_name.prohibited' => 'Identitas murid tidak dapat diubah.',
            'service_field_id.required' => 'Jenis layanan wajib dipilih.',
            'service_field_id.exists' => 'Jenis layanan tidak tersedia.',
            'session_date.required' => 'Tanggal sesi wajib diisi.',
            'session_date.before_or_equal' => 'Tanggal sesi tidak boleh berada di masa depan.',
            'problem.required' => 'Permasalahan wajib diisi.',
            'handling.required' => 'Penanganan wajib diisi.',
            'result.required' => 'Hasil wajib diisi.',
            'expected_updated_at.required' => 'Waktu versi data wajib dikirim.',
        ];
    }
}
