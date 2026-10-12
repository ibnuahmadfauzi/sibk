<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\TeacherAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassAssignmentsRequest extends FormRequest
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
            'classroom_ids' => ['required', 'array', 'min:1', 'max:100'],
            'classroom_ids.*' => ['required', 'integer', 'distinct', Rule::exists('classrooms', 'id')],
            'classroom_id' => ['prohibited'],
            'academic_year_id' => ['prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'classroom_ids.required' => 'Pilih minimal satu kelas.',
            'classroom_ids.min' => 'Pilih minimal satu kelas.',
            'classroom_ids.*.distinct' => 'Pilihan kelas tidak boleh berulang.',
        ];
    }
}
