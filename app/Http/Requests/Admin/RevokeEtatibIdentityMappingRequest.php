<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class RevokeEtatibIdentityMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageDataMaster') ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['confirmed' => ['accepted']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['confirmed.accepted' => 'Konfirmasi pembatalan wajib diberikan.'];
    }
}
