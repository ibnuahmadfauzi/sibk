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
            'only_if_unassigned' => ['sometimes', 'boolean'],
            'academic_year_id' => ['prohibited'],
            'decision_number' => ['prohibited'],
            'effective_from' => ['prohibited'],
            'effective_date' => ['prohibited'],
            'effective_until' => ['prohibited'],
            'notes' => ['prohibited'],
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
        ];
    }
}
