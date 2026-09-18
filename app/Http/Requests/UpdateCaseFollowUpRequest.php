<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\BkCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCaseFollowUpRequest extends FormRequest
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
            'follow_up_type_id' => [
                'nullable',
                'integer',
                Rule::exists('references', 'id')
                    ->where('category', 'follow_up_type')
                    ->where('is_active', true),
            ],
            'expected_updated_at' => ['required', 'date'],
        ];
    }
}
