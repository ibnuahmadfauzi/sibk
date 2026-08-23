<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\BkCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('case');

        return $case instanceof BkCase && ($this->user()?->can('assign', $case) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'assignment_type' => ['required', Rule::in(['transfer', 'additional'])],
            'to_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'reason' => ['required', 'string', 'max:5000'],
            'effective_date' => ['required', 'date'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'assignment_type.required' => 'Jenis perubahan wajib dipilih.',
            'assignment_type.in' => 'Jenis perubahan tidak valid.',
            'to_user_id.required' => 'Penerima penugasan wajib dipilih.',
            'to_user_id.exists' => 'Penerima penugasan tidak tersedia.',
            'reason.required' => 'Alasan perubahan wajib diisi.',
            'effective_date.required' => 'Tanggal berlaku wajib diisi.',
        ];
    }
}
