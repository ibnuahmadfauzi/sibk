<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\BkCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('case');

        return $case instanceof BkCase
            && ($this->user()?->can('update', $case) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'initial_info' => ['required', 'string', 'max:10000'],
            'initial_action' => ['required', 'string', 'max:10000'],
            'resolution_summary' => [Rule::requiredIf($this->input('action') === 'complete'), 'nullable', 'string', 'max:10000'],
            'action' => ['required', Rule::in(['save', 'complete'])],
            'expected_updated_at' => ['required', 'date'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'initial_info.required' => 'Latar Belakang wajib diisi.',
            'initial_action.required' => 'Penanganan wajib diisi.',
            'resolution_summary.required' => 'Catatan Penyelesaian wajib diisi untuk menyelesaikan kasus.',
            'expected_updated_at.required' => 'Muat ulang data kasus sebelum menyimpan perubahan.',
        ];
    }
}
