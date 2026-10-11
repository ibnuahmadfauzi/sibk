<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class ApplyAutomaticEtatibMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageDataMaster') ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'mode' => ['required', 'in:nisn,name'],
            'fingerprint' => ['required', 'regex:/\A[a-f0-9]{64}\z/'],
            'confirmed' => ['accepted'],
        ];
    }
}
