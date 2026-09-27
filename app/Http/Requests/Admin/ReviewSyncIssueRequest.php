<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReviewSyncIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageDataMaster') ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['use_school', 'use_etatib', 'source_correction'])],
            'membership_id' => ['required_if:action,use_school', 'nullable', 'integer'],
            'note' => ['required_if:action,source_correction', 'nullable', 'string', 'max:1000'],
        ];
    }
}
