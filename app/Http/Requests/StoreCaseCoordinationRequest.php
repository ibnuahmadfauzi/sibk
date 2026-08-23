<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\BkCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCaseCoordinationRequest extends FormRequest
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
            'waka_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'coordination_need' => ['required', 'string', 'max:10000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'waka_user_id.required' => 'Waka Kesiswaan tujuan wajib dipilih.',
            'waka_user_id.exists' => 'Waka Kesiswaan tujuan tidak tersedia.',
            'coordination_need.required' => 'Kebutuhan koordinasi wajib diisi.',
        ];
    }
}
