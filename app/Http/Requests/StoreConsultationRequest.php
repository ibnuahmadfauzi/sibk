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
            'student_id' => ['nullable', 'integer', 'required_without:temporary_nisn', 'prohibits:temporary_nisn,temporary_name', Rule::exists('students', 'id')],
            'temporary_nisn' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/', 'required_without:student_id', 'prohibits:student_id'],
            'temporary_name' => ['nullable', 'string', 'max:150', 'required_with:temporary_nisn'],
            ...$this->consultationRules(),
        ];
    }

    /** @return array<string, list<mixed>> */
    protected function consultationRules(): array
    {
        return [
            'case_id' => ['nullable', 'integer', Rule::exists('cases', 'id')->whereNull('deleted_at')],
            'service_field_id' => ['required', 'integer', Rule::exists('references', 'id')->where('category', 'service_field')->where('is_active', true)],
            'status_id' => ['required', 'integer', Rule::exists('references', 'id')->where('category', 'consultation_status')->where('is_active', true)],
            'topic' => ['required', 'string', 'max:250'],
            'referral_source' => ['nullable', 'string', 'max:150'],
            'session_date' => ['required', 'date'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i'],
            'follow_up_date' => ['nullable', 'date', 'after_or_equal:session_date'],
            'general_summary' => ['nullable', 'string', 'max:10000'],
            'internal_note' => ['nullable', 'string', 'max:10000'],
            'sensitive_content' => ['nullable', 'string', 'max:20000'],
            'conclusion' => ['nullable', 'string', 'max:10000'],
            'follow_up_plan' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'student_id.required_without' => 'Pilih murid atau isi identitas sementara.',
            'student_id.prohibits' => 'Pilih hanya satu jenis identitas murid.',
            'student_id.exists' => 'Murid yang dipilih tidak tersedia.',
            'temporary_nisn.required_without' => 'NISN sementara wajib diisi bila murid belum tersedia.',
            'temporary_nisn.regex' => 'NISN hanya boleh berisi angka.',
            'temporary_nisn.prohibits' => 'Identitas sementara tidak boleh diisi bersama murid master.',
            'temporary_name.required_with' => 'Nama sementara wajib diisi bersama NISN.',
            'case_id.exists' => 'Kasus terkait tidak tersedia.',
            'service_field_id.required' => 'Jenis layanan wajib dipilih.',
            'service_field_id.exists' => 'Jenis layanan tidak tersedia.',
            'status_id.required' => 'Status konsultasi wajib dipilih.',
            'status_id.exists' => 'Status konsultasi tidak tersedia.',
            'topic.required' => 'Topik konsultasi wajib diisi.',
            'session_date.required' => 'Tanggal sesi wajib diisi.',
            'starts_at.date_format' => 'Jam mulai harus menggunakan format jam dan menit.',
            'ends_at.date_format' => 'Jam selesai harus menggunakan format jam dan menit.',
            'follow_up_date.after_or_equal' => 'Jadwal tindak lanjut tidak boleh sebelum tanggal sesi.',
        ];
    }
}
