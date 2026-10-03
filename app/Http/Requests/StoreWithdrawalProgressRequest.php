<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\WithdrawalProgress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreWithdrawalProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', WithdrawalProgress::class) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'recorded_on' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['required', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'student_id' => 'murid',
            'recorded_on' => 'tanggal pencatatan',
            'note' => 'catatan',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'note.required' => 'Catatan wajib diisi.',
        ];
    }
}
