<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\BkCase;
use Illuminate\Foundation\Http\FormRequest;

class CompleteCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('case');

        return $case instanceof BkCase && ($this->user()?->can('update', $case) ?? false);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['expected_updated_at' => ['required', 'date']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['expected_updated_at.required' => 'Muat ulang data kasus sebelum menyelesaikan permasalahan.'];
    }
}
