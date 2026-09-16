<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Student;
use App\Models\StudentDeparture;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateStudentDepartureRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');
        $departure = $student instanceof Student ? $student->departure : null;

        return $departure instanceof StudentDeparture
            && ($this->user()?->can('update', $departure) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'departure_type' => ['required', 'string', Rule::in(StudentDeparture::types())],
            'recommendation_summary' => ['nullable', 'string', 'max:500'],
        ];
    }
}
