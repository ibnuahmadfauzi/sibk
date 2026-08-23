<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\TeacherAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TeacherAssignment::class) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'classroom_id' => ['required', 'integer', Rule::exists('classrooms', 'id')],
            'academic_year_id' => ['required', 'integer', Rule::exists('academic_years', 'id')],
            'decision_number' => ['required', 'string', 'max:150'],
            'effective_date' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'user_id.required' => 'Guru BK wajib dipilih.',
            'user_id.exists' => 'Guru BK yang dipilih tidak tersedia.',
            'classroom_id.required' => 'Kelas wajib dipilih.',
            'classroom_id.exists' => 'Kelas yang dipilih tidak tersedia.',
            'academic_year_id.required' => 'Tahun ajaran wajib dipilih.',
            'academic_year_id.exists' => 'Tahun ajaran yang dipilih tidak tersedia.',
            'decision_number.required' => 'Dasar keputusan atau nomor SK wajib diisi.',
            'decision_number.max' => 'Dasar keputusan maksimal 150 karakter.',
            'effective_date.required' => 'Tanggal mulai wajib diisi.',
            'effective_date.date' => 'Tanggal mulai tidak valid.',
            'effective_until.date' => 'Tanggal akhir tidak valid.',
            'effective_until.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.',
            'notes.max' => 'Catatan maksimal 2.000 karakter.',
        ];
    }
}
