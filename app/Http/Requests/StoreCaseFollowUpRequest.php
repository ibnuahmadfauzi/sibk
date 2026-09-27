<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\BkCase;
use App\Support\ServiceRecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCaseFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('case');

        if (! $case instanceof BkCase) {
            return false;
        }

        if (! ($this->user()?->can('update', $case) ?? false)) {
            return false;
        }

        return ! ServiceRecordStatus::isTerminal($case->status?->code);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'follow_up_type_id' => [
                'required',
                'integer',
                Rule::exists('references', 'id')
                    ->where('category', 'follow_up_type')
                    ->where('is_active', true),
            ],
            'follow_up_date' => [
                'required',
                'date',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
