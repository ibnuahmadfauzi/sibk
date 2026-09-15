<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Student;
use App\Models\StudentDeparture;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreStudentDepartureRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student instanceof Student
            && ($this->user()?->can('create', [StudentDeparture::class, $student]) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'departure_type' => ['required', 'string', Rule::in(StudentDeparture::types())],
            'reported_at' => ['required', 'date', 'before_or_equal:today'],
            'recommendation_summary' => ['nullable', 'string', 'max:500'],
        ];
    }
}
