<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Consultation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Consultation::class) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'student_id' => ['nullable', 'integer', 'required_without_all:temporary_student_id,temporary_nisn', 'prohibits:temporary_student_id,temporary_nisn,temporary_name', Rule::exists('students', 'id')],
            'temporary_student_id' => ['nullable', 'integer', 'required_without_all:student_id,temporary_nisn', 'prohibits:student_id,temporary_nisn,temporary_name', Rule::exists('temporary_students', 'id')->whereNull('deleted_at')],
            'temporary_nisn' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/', 'required_without_all:student_id,temporary_student_id', 'prohibits:student_id,temporary_student_id'],
            'temporary_name' => ['nullable', 'string', 'max:150', 'required_with:temporary_nisn'],
            'temporary_classroom_id' => ['nullable', 'integer', 'prohibits:student_id', Rule::exists('classrooms', 'id')],
            ...$this->consultationRules(),
        ];
    }

    /** @return array<string, list<mixed>> */
    protected function consultationRules(): array
    {
        return [
            'service_field_id' => ['required', 'integer', Rule::exists('references', 'id')->where('category', 'service_field')->where('is_active', true)],
            'session_date' => ['required', 'date', 'before_or_equal:today'],
            'problem' => ['required', 'string', 'max:10000'],
            'handling' => ['required', 'string', 'max:10000'],
            'result' => ['required', 'string', 'max:10000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'student_id.required_without' => 'Pilih murid atau isi identitas sementara.',
            'student_id.prohibits' => 'Pilih hanya satu jenis identitas murid.',
            'student_id.exists' => 'Murid yang dipilih tidak tersedia.',
            'temporary_student_id.exists' => 'Identitas sementara tidak tersedia.',
            'temporary_nisn.required_without' => 'NISN sementara wajib diisi bila murid belum tersedia.',
            'temporary_nisn.regex' => 'NISN hanya boleh berisi angka.',
            'temporary_nisn.prohibits' => 'Identitas sementara tidak boleh diisi bersama murid master.',
            'temporary_name.required_with' => 'Nama sementara wajib diisi bersama NISN.',
            'service_field_id.required' => 'Jenis layanan wajib dipilih.',
            'service_field_id.exists' => 'Jenis layanan tidak tersedia.',
            'session_date.required' => 'Tanggal sesi wajib diisi.',
            'session_date.before_or_equal' => 'Tanggal sesi tidak boleh berada di masa depan.',
            'problem.required' => 'Permasalahan wajib diisi.',
            'handling.required' => 'Penanganan wajib diisi.',
            'result.required' => 'Hasil wajib diisi.',
        ];
    }
}
