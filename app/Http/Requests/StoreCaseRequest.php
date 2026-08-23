<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\BkCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', BkCase::class) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'student_id' => ['nullable', 'integer', 'required_without:temporary_nisn', 'prohibits:temporary_nisn,temporary_name', Rule::exists('students', 'id')],
            'temporary_nisn' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/', 'required_without:student_id', 'prohibits:student_id'],
            'temporary_name' => ['nullable', 'string', 'max:150', 'required_with:temporary_nisn'],
            'case_source_id' => ['required', 'integer', Rule::exists('references', 'id')->where('category', 'case_source')->where('is_active', true)],
            'service_field_id' => ['required', 'integer', Rule::exists('references', 'id')->where('category', 'service_field')->where('is_active', true)],
            'service_date' => ['required', 'date', 'before_or_equal:today'],
            'referrer' => ['nullable', 'string', 'max:150'],
            'initial_info' => ['required', 'string', 'max:10000'],
            'initial_action' => ['required', 'string', 'max:10000'],
            'internal_note' => ['nullable', 'string', 'max:10000'],
            'etatib_record_ids' => ['sometimes', 'array'],
            'etatib_record_ids.*' => ['integer', 'distinct', Rule::exists('external_tatib_records', 'id')->where('is_active', true)],
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
            'case_source_id.required' => 'Sumber kasus wajib dipilih.',
            'case_source_id.exists' => 'Sumber kasus tidak tersedia.',
            'service_field_id.required' => 'Bidang layanan wajib dipilih.',
            'service_field_id.exists' => 'Bidang layanan tidak tersedia.',
            'service_date.required' => 'Tanggal layanan wajib diisi.',
            'service_date.before_or_equal' => 'Tanggal layanan tidak boleh berada di masa depan.',
            'initial_info.required' => 'Informasi awal wajib diisi.',
            'initial_action.required' => 'Penanganan awal wajib diisi.',
            'etatib_record_ids.*.exists' => 'Data e-Tatib yang dipilih tidak tersedia.',
            'etatib_record_ids.*.distinct' => 'Data e-Tatib tidak boleh dipilih lebih dari sekali.',
        ];
    }
}
