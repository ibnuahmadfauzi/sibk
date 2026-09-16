<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\BkCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCaseCoordinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('case');

        return $case instanceof BkCase && ($this->user()?->can('coordinate', $case) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'status_id' => ['required', 'integer', Rule::exists('references', 'id')->where('category', 'coordination_status')->where('is_active', true)],
            'result' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'status_id.required' => 'Status koordinasi wajib dipilih.',
            'status_id.exists' => 'Status koordinasi tidak tersedia.',
        ];
    }
}
