<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Student;
use App\Models\StudentDeparture;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class FinalizeStudentDepartureRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');
        $departure = $student instanceof Student ? $student->departure : null;

        return $departure instanceof StudentDeparture
            && ($this->user()?->can('finalize', $departure) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in([StudentDeparture::STATUS_CANCELLED, StudentDeparture::STATUS_OFFICIAL])],
            'effective_date' => [Rule::requiredIf($this->string('decision')->is(StudentDeparture::STATUS_OFFICIAL)), 'nullable', 'date'],
            'decision_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
